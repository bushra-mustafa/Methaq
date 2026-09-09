"""Original Methaq demonstration chime; no recordings or third-party samples."""
import math
import struct
import wave
from pathlib import Path

sample_rate = 22050
duration = 12
notes = [(0.4, 523.25), (1.8, 659.25), (3.2, 783.99), (4.6, 1046.5), (6.2, 783.99), (7.6, 659.25)]
output = Path(__file__).resolve().parents[2] / 'public/brand/audio/methaq-chime.wav'
with wave.open(str(output), 'wb') as stream:
    stream.setnchannels(1)
    stream.setsampwidth(2)
    stream.setframerate(sample_rate)
    for index in range(sample_rate * duration):
        t = index / sample_rate
        value = 0.0
        for start, frequency in notes:
            age = t - start
            if 0 <= age <= 4:
                envelope = min(age / 0.04, 1) * math.exp(-age * 1.6) * min((4-age)/0.2, 1)
                value += 0.18 * envelope * (math.sin(2*math.pi*frequency*age) + .2*math.sin(2*math.pi*frequency*2*age))
        stream.writeframesraw(struct.pack('<h', round(max(-1, min(1, value))*32767)))
