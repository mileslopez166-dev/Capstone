"""Extract the original thermometer diagrams, without resampling, from the supplied PDF."""
import sys
from pathlib import Path

import pymupdf

root = Path(__file__).resolve().parents[1]
destination = root / 'resources' / 'worksheets' / 'aral-g6'
document = pymupdf.open(sys.argv[1])
for part, page_index in enumerate([68, 69], start=1):
    page = document[page_index]
    figures = []
    for entry in page.get_images():
        if entry[2] > 200 or entry[3] < 400:
            continue
        rect = page.get_image_rects(entry[0])[0]
        figures.append((round(rect.y0 / 100), rect.x0, entry[0]))
    assert len(figures) == 8
    for index, (_, _, xref) in enumerate(sorted(figures), start=1):
        image = document.extract_image(xref)
        name = f'thermometer-{(part - 1) * 8 + index}.{image["ext"]}'
        (destination / name).write_bytes(image['image'])
        print(name)
