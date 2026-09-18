"""Render the supplied ARAL workbook without retyping or changing its problems.

Run with PyMuPDF installed and the source PDF as the first argument.
"""
import json
import re
import sys
from pathlib import Path

import pymupdf

root = Path(__file__).resolve().parents[1]
destination = root / 'resources' / 'worksheets' / 'aral-g6'
destination.mkdir(parents=True, exist_ok=True)
document = pymupdf.open(sys.argv[1])
worksheets = {}
for index, page in enumerate(document):
    match = re.search(r'ARAL Math Grade 6 Worksheet (\d+)(?: \(Part (\d+)\))?', page.get_text())
    if not match:
        continue
    number = int(match[1])
    # The back cover has a hidden Worksheet 36 heading, but contains no exercise.
    if number == 36:
        continue
    part = int(match[2] or 1)
    filename = f'worksheet-{number:02d}-part-{part}.jpg'
    pixmap = page.get_pixmap(matrix=pymupdf.Matrix(1.7, 1.7), alpha=False)
    pixmap.save(destination / filename, jpg_quality=88)
    worksheet = worksheets.setdefault(number, {'number': number, 'title': f'ARAL Math Grade 6 - Worksheet {number}', 'pages': []})
    worksheet['pages'].append({'part': part, 'source_page': index + 1, 'image': filename, 'width': pixmap.width, 'height': pixmap.height})

assert sorted(worksheets) == list(range(1, 36)), 'Expected all 35 worksheets'
document.select([1])
document.save(destination / 'attribution.pdf')
catalog = {'title': 'ARAL Mathematics - Grade 6', 'source': "[FINAL - RTP] ARAL Math G6 Learner's Worksheets.pdf", 'publisher': 'Department of Education; includes materials adapted from iSipnayan (OSSFF).', 'worksheets': list(worksheets.values())}
(root / 'resources' / 'data' / 'numeracy-worksheets.json').write_text(json.dumps(catalog, indent=2) + '\n', encoding='utf-8')
print(f'Rendered {len(worksheets)} worksheets, {sum(len(w["pages"]) for w in worksheets.values())} pages.')
