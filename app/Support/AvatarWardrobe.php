<?php

namespace App\Support;

class AvatarWardrobe
{
    public static function options(): array
    {
        return [
            'character' => ['label' => 'Character', 'tab' => 'character', 'icon' => 'face', 'items' => [
                'nova' => ['label' => 'Nova', 'detail' => 'Boy'],
                'lyra' => ['label' => 'Lyra', 'detail' => 'Girl'],
            ]],
            'skin' => ['label' => 'Skin tone', 'tab' => 'character', 'items' => [
                'peach' => ['label' => 'Peach', 'color' => '#ffceaa'],
                'warm' => ['label' => 'Warm', 'color' => '#e9ad7f'],
                'golden' => ['label' => 'Golden', 'color' => '#c88b5b'],
                'brown' => ['label' => 'Brown', 'color' => '#986342'],
                'deep' => ['label' => 'Deep', 'color' => '#694733'],
            ]],
            'hair' => ['label' => 'Hair color', 'tab' => 'character', 'items' => [
                'brown' => ['label' => 'Chestnut', 'color' => '#684539'],
                'black' => ['label' => 'Midnight', 'color' => '#30313d'],
                'blond' => ['label' => 'Honey', 'color' => '#d69e48'],
                'auburn' => ['label' => 'Auburn', 'color' => '#a74f38'],
                'rose' => ['label' => 'Rose', 'color' => '#e995b8'],
                'aqua' => ['label' => 'Aqua', 'color' => '#57b8bb'],
                'silver' => ['label' => 'Silver', 'color' => '#ced6dd'],
            ]],
            'outfit' => ['label' => 'Outfit', 'tab' => 'clothes', 'icon' => 'apparel', 'items' => [
                'hoodie' => ['label' => 'Cozy hoodie'],
                'varsity' => ['label' => 'Varsity jacket'],
                'explorer' => ['label' => 'Explorer vest'],
                'jersey' => ['label' => 'Team jersey'],
                'bomber' => ['label' => 'Cloud bomber', 'cost' => 50, 'collection' => 'nova'],
                'captain_jacket' => ['label' => 'Captain jacket', 'cost' => 75, 'collection' => 'nova'],
                'space_suit' => ['label' => 'Space cadet', 'cost' => 100, 'collection' => 'nova'],
                'racer_jacket' => ['label' => 'Racer jacket', 'cost' => 75, 'collection' => 'nova'],
                'cardigan' => ['label' => 'Heart cardigan', 'cost' => 50, 'collection' => 'lyra'],
                'sailor_blouse' => ['label' => 'Sailor blouse', 'cost' => 50, 'collection' => 'lyra'],
                'starlight_top' => ['label' => 'Starlight top', 'cost' => 75, 'collection' => 'lyra'],
            ]],
            'color' => ['label' => 'Outfit color', 'tab' => 'clothes', 'items' => [
                'ocean' => ['label' => 'Ocean', 'color' => '#3285dc'],
                'mint' => ['label' => 'Mint', 'color' => '#30a58c'],
                'coral' => ['label' => 'Coral', 'color' => '#e9716a'],
                'berry' => ['label' => 'Berry', 'color' => '#bc5888'],
                'sunshine' => ['label' => 'Sunshine', 'color' => '#e8b840'],
                'cloud' => ['label' => 'Cloud', 'color' => '#e3e9ef'],
            ]],
            'bottoms' => ['label' => 'Bottoms', 'tab' => 'clothes', 'icon' => 'checkroom', 'items' => [
                'jeans' => ['label' => 'Everyday jeans'],
                'shorts' => ['label' => 'Adventure shorts'],
                'skirt' => ['label' => 'Everyday skirt'],
                'cargo_pants' => ['label' => 'Explorer cargos', 'cost' => 50, 'collection' => 'nova'],
                'pleated_skirt' => ['label' => 'Ribbon skirt', 'cost' => 50, 'collection' => 'lyra'],
                'tutu_skirt' => ['label' => 'Cloud tutu', 'cost' => 75, 'collection' => 'lyra'],
            ]],
            'shoes' => ['label' => 'Shoes', 'tab' => 'clothes', 'icon' => 'steps', 'items' => [
                'sneakers' => ['label' => 'Sneakers'],
                'high_tops' => ['label' => 'High tops'],
                'boots' => ['label' => 'Trail boots'],
                'comet_sneakers' => ['label' => 'Comet sneakers', 'cost' => 50, 'collection' => 'nova'],
                'ribbon_flats' => ['label' => 'Ribbon flats', 'cost' => 50, 'collection' => 'lyra'],
            ]],
            'headwear' => ['label' => 'Headwear', 'tab' => 'accessories', 'icon' => 'headphones', 'items' => [
                'none' => ['label' => 'No headwear'],
                'cap' => ['label' => 'Adventure cap'],
                'beanie' => ['label' => 'Cozy beanie'],
                'headphones' => ['label' => 'Headphones'],
                'star_cap' => ['label' => 'Star cap', 'cost' => 25],
                'crown' => ['label' => 'Practice crown', 'cost' => 75],
                'aviator_goggles' => ['label' => 'Sky goggles', 'cost' => 50, 'collection' => 'nova'],
                'sailor_cap' => ['label' => 'Sailor cap', 'cost' => 50, 'collection' => 'nova'],
                'ribbon_bow' => ['label' => 'Ribbon bow', 'cost' => 25, 'collection' => 'lyra'],
                'flower_crown' => ['label' => 'Daisy crown', 'cost' => 75, 'collection' => 'lyra'],
                'beret' => ['label' => 'Artist beret', 'cost' => 50, 'collection' => 'lyra'],
            ]],
            'accessory' => ['label' => 'Extra detail', 'tab' => 'accessories', 'icon' => 'eyeglasses', 'items' => [
                'none' => ['label' => 'No accessory'],
                'glasses' => ['label' => 'Round glasses'],
                'backpack' => ['label' => 'School backpack'],
                'scarf' => ['label' => 'Adventure scarf'],
                'medal' => ['label' => 'Practice medal', 'cost' => 50],
                'messenger_bag' => ['label' => 'Explorer satchel', 'cost' => 50, 'collection' => 'nova'],
                'explorer_watch' => ['label' => 'Adventure watch', 'cost' => 25, 'collection' => 'nova'],
                'star_purse' => ['label' => 'Starlight bag', 'cost' => 50, 'collection' => 'lyra'],
            ]],
        ];
    }

    public static function defaults(?string $gender = null): array
    {
        return [
            'character' => $gender === 'female' ? 'lyra' : 'nova',
            'skin' => 'warm', 'hair' => 'brown', 'outfit' => 'hoodie', 'color' => 'ocean',
            'bottoms' => 'jeans', 'shoes' => 'sneakers', 'headwear' => 'none', 'accessory' => 'none',
        ];
    }

    public static function resolve(?array $appearance, ?string $gender = null): array
    {
        $resolved = self::defaults($gender);

        foreach (self::options() as $key => $option) {
            $value = $appearance[$key] ?? null;
            if (is_string($value) && isset($option['items'][$value])) {
                $resolved[$key] = $value;
            }
        }

        return $resolved;
    }
}
