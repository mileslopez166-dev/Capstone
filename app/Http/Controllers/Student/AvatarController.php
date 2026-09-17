<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Support\AvatarWardrobe;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AvatarController extends Controller
{
    public function edit(Request $request): View
    {
        abort_unless($request->user()->isStudent(), 403);

        $unlocked = $request->user()->unlockedAvatarItems();
        $options = AvatarWardrobe::options();
        foreach ($options as $key => &$option) {
            foreach ($option['items'] as $value => &$item) {
                $item['locked'] = isset($item['cost']) && !in_array($key.':'.$value, $unlocked, true);
            }
            unset($item);
        }
        unset($option);

        return view('student.wardrobe', [
            'student' => $request->user(),
            'options' => $options,
            'coinBalance' => $request->user()->practiceCoinBalance(),
            'appearance' => AvatarWardrobe::resolve($request->user()->avatar_config, $request->user()->gender),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isStudent(), 403);

        $options = AvatarWardrobe::options();
        $rules = ['avatar' => ['required', 'array:'.implode(',', array_keys($options))]];
        foreach ($options as $key => $option) {
            $rules['avatar.'.$key] = ['required', 'string', Rule::in(array_keys($option['items']))];
        }

        $validated = $request->validate($rules);
        $unlocked = $request->user()->unlockedAvatarItems();
        foreach ($validated['avatar'] as $key => $value) {
            if (isset($options[$key]['items'][$value]['cost']) && !in_array($key.':'.$value, $unlocked, true)) {
                throw ValidationException::withMessages(['avatar.'.$key => 'Unlock this item with practice coins first.']);
            }
        }
        $request->user()->avatar_config = $validated['avatar'];
        $request->user()->save();

        return redirect()->route('student.wardrobe.edit')->with('status', 'avatar-saved');
    }

    public function purchase(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isStudent(), 403);
        $data = $request->validate(['item' => ['required', 'string', 'max:80']]);
        $parts = explode(':', $data['item']);
        $item = count($parts) === 2 ? (AvatarWardrobe::options()[$parts[0]]['items'][$parts[1]] ?? null) : null;
        if (!$item || !isset($item['cost'])) {
            throw ValidationException::withMessages(['item' => 'Choose a reward from the wardrobe.']);
        }

        DB::transaction(function () use ($request, $data, $item) {
            $student = User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            if (in_array($data['item'], $student->unlockedAvatarItems(), true)) {
                return;
            }
            if ($student->practiceCoinBalance() < $item['cost']) {
                throw ValidationException::withMessages(['item' => 'You need '.$item['cost'].' practice coins for this item.']);
            }
            $student->practiceCoinTransactions()->create([
                'item_key' => $data['item'], 'amount' => -$item['cost'],
                'description' => 'Unlocked '.$item['label'],
            ]);
        });

        return redirect()->route('student.wardrobe.edit', [
            'tab' => AvatarWardrobe::options()[$parts[0]]['tab'],
            'collection' => $item['collection'] ?? 'all',
        ])->with('status', 'wardrobe-unlocked');
    }
}
