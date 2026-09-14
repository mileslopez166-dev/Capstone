# Campus Design Assets

Generated with the built-in imagegen tool for the student and teacher pixel-campus theme. The supplied September 14 screenshot was the UI style reference. The PNG files are stored locally, so rendering them does not depend on an external image service.

| Asset | Location | Use |
| --- | --- | --- |
| Nova Finch | `public/images/campus/student-boy.png` | Boy student portrait and full-body character |
| Lyra Vale | `public/images/campus/student-girl.png` | Girl student portrait and full-body character |
| Campus landscape | `public/images/campus/pixel-world.png` | Shared student/teacher background |

The character files retain their generated transparent alpha channel. The student's saved `gender`/avatar-style choice selects the character; the existing profile update stores that choice. Teacher accounts retain their initials. A teacher viewing a student's record sees that student's avatar.

The student header uses saved assessment `points` as XP. Each 500 XP advances one display level, beginning at level 1. This display does not change scores, points, leaderboard ranking, or retake permissions. The active assessment game screen keeps its existing layout.

## Generation Prompts

### Nova Finch

Use case: stylized-concept. Asset type: transparent PNG full-body pixel art student boy mascot for an educational web app sidebar. Create an original friendly Filipino schoolboy with warm medium skin, large cheerful eyes, tousled dark brown hair, white hoodie with cobalt blue sleeves and hood, blue backpack, navy pants and blue-white sneakers. Holds a closed schoolbook under his left arm, right hand pointing to the right and slightly upward. Confident encouraging smile. Crisp premium 32-bit pixel-art RPG character with dark pixel outlines and clearly stepped pixel clusters, carefully shaded clothing, similar to a polished educational video game. Full body visible head to toe, front three-quarter view. Single character only, centered on a genuinely transparent alpha background, no ground, no scenery, no words, no UI, no watermark, no shadow outside the character. Portrait canvas 1024x1536. Character fills 88 percent of canvas height, all hair hands backpack and feet contained.

### Lyra Vale

Reference: the generated Nova Finch asset.

Use case: stylized-concept. Reference image: match this original student-boy mascot's premium outlined pixel-art style and chibi proportions to create a matching GIRL companion, NOT the same boy. Original friendly Filipino schoolgirl, warm medium skin, big cheerful brown eyes, long dark brown ponytail, small coral hair tie. White school hoodie with teal hood and sleeves, coral backpack, dark navy trousers, teal and white sneakers. Holding a small closed schoolbook, free hand waves. Full-body front three-quarter view smiling, top of hair to soles fully contained. Single character centered, fills 88% of portrait 1024x1536 canvas. Truly transparent alpha background, NO black background, no colored halo/glow, no scenery, no ground, no shadow, no words or UI. Crisp carefully shaded 32-bit RPG pixel art suitable for a cheerful sky-blue educational website.

### Campus Landscape

Use case: stylized-concept. Asset type: full-bleed background for an educational pixel game dashboard, landscape 1536x1024. Original crisp 16-bit pixel-art bright daytime school adventure world. Most of the image (upper 85%) is an open light azure sky with a few sparse large blocky white clouds near top corners, spacious calm sky in the center for overlaying real HTML panels. Along bottom 15%, a side-scrolling landscape with bright emerald grass platform edges, small pixel shrubs and green trees toward corners, earthy square ground tiles, distant pale sky-blue silhouette of a little school with flag on bottom right. Bright cheerful cyan, white, leaf green with tiny yellow flowers, light and friendly. Clearly stepped square edges with chunky pixel clusters, no smooth vector shapes, no 3D. No characters, no avatars, no text, no icons, no menus, no UI elements, no panels or frames, no logos or watermark. This is only the actual decorative landscape image, not a screenshot of a website.
