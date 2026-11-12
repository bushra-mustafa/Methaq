/**
 * Animates a still smoke photograph so it slowly drifts and curls.
 * WebGL is preferred; a 2D canvas strip-warp is used when WebGL is unavailable.
 * Ported from the owner's anas-lamar-invitation reference project.
 */
export interface SmokeRenderer {
    resize: (width: number, height: number) => void;
    paint: (time: number, pointerX: number, pointerY: number) => void;
    destroy: () => void;
}

export interface SmokeRendererHandle {
    renderer: SmokeRenderer;
    canvas: HTMLCanvasElement;
}

const VERTEX = `attribute vec2 a_position;varying vec2 v_uv;
void main(){v_uv=a_position*.5+.5;gl_Position=vec4(a_position,0.,1.);}`;

const FRAGMENT = `
precision mediump float;
varying vec2 v_uv;
uniform sampler2D u_image;
uniform float u_time;
uniform vec2 u_crop;
uniform vec2 u_pointer;
float hash(vec2 p){return fract(sin(dot(p,vec2(127.1,311.7)))*43758.5453);}
float noise(vec2 p){vec2 i=floor(p),f=fract(p);f=f*f*(3.-2.*f);return mix(mix(hash(i),hash(i+vec2(1,0)),f.x),mix(hash(i+vec2(0,1)),hash(i+vec2(1,1)),f.x),f.y);}
float fbm(vec2 p){float v=0.;v+=.55*noise(p);p=mat2(.8,-.6,.6,.8)*p*2.04;v+=.28*noise(p);p=p*2.03;v+=.14*noise(p);return v;}
void main(){
  float t=u_time*.12;
  vec2 uv=(v_uv-.5)*u_crop+.5;
  uv=(uv-.5)/(1.025+.010*sin(t*.52))+.5;
  vec2 drift=vec2(sin(uv.y*8.+t*.76),cos(uv.x*7.-t*.64));
  vec2 curl=vec2(fbm(uv*4.2+vec2(t*.13,-t*.11)),fbm(uv*4.1+vec2(17.2+t*.12,5.7+t*.14)))-.5;
  vec2 flow=.012*drift+.030*curl;
  uv+=u_pointer*.003;
  vec3 base=texture2D(u_image,clamp(uv+flow,0.003,.997)).rgb;
  vec3 veil=texture2D(u_image,clamp((uv-.5)*1.008+.5-flow*.7,0.003,.997)).rgb;
  vec3 color=mix(base,veil,.15);
  color*=.978+.034*sin(t*.73+uv.y*5.);
  color*=vec3(1.02,.975,1.005);
  gl_FragColor=vec4(color,1.);
}`;

function createWebGLRenderer(canvas: HTMLCanvasElement, image: HTMLImageElement): SmokeRenderer | null {
    const gl = canvas.getContext('webgl', { alpha: false, antialias: false, depth: false, stencil: false, powerPreference: 'low-power' });
    if (!gl) {
        return null;
    }
    const shaders: WebGLShader[] = [];
    const program = gl.createProgram();
    const buffer = gl.createBuffer();
    const texture = gl.createTexture();
    const destroy = (): void => {
        shaders.forEach((shader) => gl.deleteShader(shader));
        gl.deleteBuffer(buffer);
        gl.deleteTexture(texture);
        gl.deleteProgram(program);
    };
    if (!program || !buffer || !texture) {
        destroy();

        return null;
    }
    for (const [type, source] of [[gl.VERTEX_SHADER, VERTEX], [gl.FRAGMENT_SHADER, FRAGMENT]] as const) {
        const shader = gl.createShader(type);
        if (!shader) {
            destroy();

            return null;
        }
        shaders.push(shader);
        gl.shaderSource(shader, source);
        gl.compileShader(shader);
        if (!gl.getShaderParameter(shader, gl.COMPILE_STATUS)) {
            destroy();

            return null;
        }
        gl.attachShader(program, shader);
    }
    gl.linkProgram(program);
    if (!gl.getProgramParameter(program, gl.LINK_STATUS)) {
        destroy();

        return null;
    }
    gl.useProgram(program);
    gl.bindBuffer(gl.ARRAY_BUFFER, buffer);
    gl.bufferData(gl.ARRAY_BUFFER, new Float32Array([-1, -1, 1, -1, -1, 1, -1, 1, 1, -1, 1, 1]), gl.STATIC_DRAW);
    const position = gl.getAttribLocation(program, 'a_position');
    gl.enableVertexAttribArray(position);
    gl.vertexAttribPointer(position, 2, gl.FLOAT, false, 0, 0);
    gl.activeTexture(gl.TEXTURE0);
    gl.bindTexture(gl.TEXTURE_2D, texture);
    gl.pixelStorei(gl.UNPACK_FLIP_Y_WEBGL, true);
    gl.texParameteri(gl.TEXTURE_2D, gl.TEXTURE_WRAP_S, gl.CLAMP_TO_EDGE);
    gl.texParameteri(gl.TEXTURE_2D, gl.TEXTURE_WRAP_T, gl.CLAMP_TO_EDGE);
    gl.texParameteri(gl.TEXTURE_2D, gl.TEXTURE_MIN_FILTER, gl.LINEAR);
    gl.texParameteri(gl.TEXTURE_2D, gl.TEXTURE_MAG_FILTER, gl.LINEAR);
    gl.texImage2D(gl.TEXTURE_2D, 0, gl.RGB, gl.RGB, gl.UNSIGNED_BYTE, image);
    gl.uniform1i(gl.getUniformLocation(program, 'u_image'), 0);
    const timeUniform = gl.getUniformLocation(program, 'u_time');
    const cropUniform = gl.getUniformLocation(program, 'u_crop');
    const pointerUniform = gl.getUniformLocation(program, 'u_pointer');
    let aspect = 1;

    return {
        resize(width, height) {
            aspect = width / height;
            gl.viewport(0, 0, canvas.width, canvas.height);
        },
        paint(time, pointerX, pointerY) {
            const imageAspect = image.naturalWidth / image.naturalHeight;
            gl.uniform2f(cropUniform, Math.min(1, aspect / imageAspect), Math.min(1, imageAspect / aspect));
            gl.uniform2f(pointerUniform, pointerX, pointerY);
            gl.uniform1f(timeUniform, time);
            gl.drawArrays(gl.TRIANGLES, 0, 6);
        },
        destroy,
    };
}

function createCanvasRenderer(canvas: HTMLCanvasElement, image: HTMLImageElement): SmokeRenderer | null {
    const context = canvas.getContext('2d', { alpha: false });
    const base = document.createElement('canvas');
    const pass = document.createElement('canvas');
    const baseContext = base.getContext('2d', { alpha: false });
    const passContext = pass.getContext('2d', { alpha: false });
    if (!context || !baseContext || !passContext) {
        return null;
    }

    return {
        resize() {
            base.width = canvas.width + 72;
            base.height = canvas.height + 72;
            pass.width = canvas.width + 36;
            pass.height = canvas.height + 36;
        },
        paint(time) {
            const width = canvas.width;
            const height = canvas.height;
            const pad = 18;
            const t = time * 0.12;
            const scale = Math.max(base.width / image.naturalWidth, base.height / image.naturalHeight) * (1.006 + 0.006 * Math.sin(t * 0.52));
            const drawnWidth = image.naturalWidth * scale;
            const drawnHeight = image.naturalHeight * scale;
            baseContext.drawImage(image, (base.width - drawnWidth) / 2, (base.height - drawnHeight) / 2, drawnWidth, drawnHeight);
            const row = Math.max(3, Math.ceil(pass.height / 96));
            for (let y = 0; y < pass.height; y += row) {
                const strip = Math.min(row + 1, pass.height - y);
                const v = y / pass.height;
                const dx = width * (0.01 * Math.sin(v * 8 + t * 0.76) + 0.004 * Math.sin(v * 17 - t * 0.48));
                passContext.drawImage(base, pad - dx, pad + y, pass.width, strip, 0, y, pass.width, strip);
            }
            const column = Math.max(3, Math.ceil(width / 64));
            for (let x = 0; x < width; x += column) {
                const strip = Math.min(column + 1, width - x);
                const u = x / width;
                const dy = height * (0.007 * Math.cos(u * 7 - t * 0.64) + 0.002 * Math.sin(u * 13 + t * 0.35));
                context.drawImage(pass, pad + x, pad - dy, strip, height, x, 0, strip, height);
            }
        },
        destroy() {
            base.width = 0;
            pass.width = 0;
        },
    };
}

export function createSmokeRenderer(glCanvas: HTMLCanvasElement, fallbackCanvas: HTMLCanvasElement, image: HTMLImageElement): SmokeRendererHandle | null {
    const renderer = createWebGLRenderer(glCanvas, image);
    if (renderer) {
        return { renderer, canvas: glCanvas };
    }
    const fallback = createCanvasRenderer(fallbackCanvas, image);

    return fallback ? { renderer: fallback, canvas: fallbackCanvas } : null;
}
