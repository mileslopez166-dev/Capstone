# AI-PGAALS Prototype Label Mapping

The public datasets used for model development may not include official Phil-IRI labels. For the prototype ML model, labels are created from literacy achievement indicators only.

These labels are training labels for prediction support. They do not replace Phil-IRI scoring, teacher review, or official assessment interpretation.

## Default Thresholds

When a dataset provides a usable literacy score from 0 to 100:

| Literacy Score | Prototype Label |
| --- | --- |
| 80 to 100 | Independent |
| 59 to 79.99 | Instructional |
| 0 to 58.99 | Frustration |

These thresholds follow the same broad reading-level bands already used by AI-PGAALS comprehension interpretation. They are used only to create transparent prototype labels for model training.

## Source Score Conversion

| Source | Conversion |
| --- | --- |
| CRLA aggregate percentages | Weighted score from low emerging, high emerging, developing, transitioning, and at-grade-level percentages |
| NAT MPS columns | Used directly as 0-100 scores |
| LaNA plausible values | Averaged, then scaled to a 0-100 prototype score |
| Student performance 0-20 grades | Multiplied by 5 to create a 0-100 prototype score |

## Achievement-Level Text Mapping

When a dataset provides a text category instead of a numeric score:

| Source Category Keywords | Prototype Label |
| --- | --- |
| high, advanced, proficient, independent, exceeds | Independent |
| medium, satisfactory, developing, instructional, near proficient | Instructional |
| low, beginning, frustration, below basic, needs support | Frustration |

Unrecognized categories are left unlabeled and excluded from training until reviewed.

## Limitation

These mappings are explainable starting rules. A teacher or researcher should review them before using the model for capstone evaluation or deployment.
