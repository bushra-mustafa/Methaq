import type { ImgHTMLAttributes } from "react";

interface ThumbnailProps extends Omit<ImgHTMLAttributes<HTMLImageElement>, "src" | "width" | "height"> {
    src: string;
    /** The smallest twin (AVIF), then WebP, when the server found them; other browsers keep the PNG. */
    avif?: string | null;
    webp?: string | null;
    alt: string;
    width: number;
    height: number;
}

/** A preview image that reserves its space (no layout shift), loads lazily and prefers AVIF and WebP. */
export default function Thumbnail({ src, avif, webp, alt, width, height, loading = "lazy", decoding = "async", ...rest }: ThumbnailProps) {
    return (
        <picture>
            {avif && <source srcSet={avif} type="image/avif" />}
            {webp && <source srcSet={webp} type="image/webp" />}
            <img src={src} alt={alt} width={width} height={height} loading={loading} decoding={decoding} {...rest} />
        </picture>
    );
}
