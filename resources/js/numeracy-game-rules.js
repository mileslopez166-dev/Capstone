export const gameNames = { rocket: 'Rocket Launch', puzzle: 'Number Puzzle', archer: 'Divisibility Archer' };

export function validDigits(game) {
    if (!/^[\d]*_[\d]*$/.test(game.pattern) || !Number.isSafeInteger(game.divisor) || game.divisor <= 0) return [];
    return Array.from({ length: 10 }, (_, digit) => String(digit)).filter(digit => {
        if (game.pattern.startsWith('_') && game.pattern.length > 1 && digit === '0') return false;
        const number = Number(game.pattern.replace('_', digit));
        return Number.isSafeInteger(number) && number % game.divisor === 0;
    });
}

export function gradeGameAnswer(game, answer) {
    if (game.type === 'puzzle') {
        const digits = validDigits(game);
        if (!digits.length || typeof answer !== 'string' || !/^\d$/.test(answer)) return null;
        const expected = game.smallest ? digits.slice(0, 1) : digits;
        const number = Number(game.pattern.replace('_', expected[0]));
        return { correct: expected.includes(answer), explanation: `${game.smallest ? 'The smallest digit is' : 'Valid digits:'} ${expected.join(' or ')}. ${number.toLocaleString()} / ${game.divisor} = ${number / game.divisor}.` };
    }
    if (!Number.isSafeInteger(game.number) || game.number < 0) return null;
    if (game.type === 'rocket') {
        if (!['Yes', 'No'].includes(answer) || !Number.isSafeInteger(game.divisor) || game.divisor <= 0) return null;
        const remainder = game.number % game.divisor;
        return { correct: answer === (remainder === 0 ? 'Yes' : 'No'), explanation: remainder === 0
            ? `${game.number.toLocaleString()} / ${game.divisor} = ${game.number / game.divisor}, with no remainder.`
            : `${game.number.toLocaleString()} / ${game.divisor} leaves a remainder of ${remainder}.` };
    }
    if (game.type === 'archer') {
        if (!Array.isArray(answer) || !answer.every(value => game.options.includes(value)) || new Set(answer).size !== answer.length) return null;
        const expected = game.options.filter(value => Number(value) > 0 && game.number % Number(value) === 0);
        return { correct: answer.length === expected.length && expected.every(value => answer.includes(value)), explanation: expected.length
            ? `${game.number.toLocaleString()} is divisible by ${expected.join(', ')}.`
            : `None of these targets divides ${game.number.toLocaleString()} evenly.` };
    }
    return null;
}

export function gameAnswerExists(game, values = {}) {
    return Object.hasOwn(values, game.field) && gradeGameAnswer(game, values[game.field]) !== null
        && (!game.reason || typeof values.reason === 'string' && values.reason.trim().length > 0);
}

export function gameHint(game) {
    const rules = { 2: 'The last digit must be even.', 3: 'Add the digits. Their sum must be divisible by 3.',
        4: 'The last two digits must form a multiple of 4.', 5: 'The last digit must be 0 or 5.',
        6: 'The number must be divisible by both 2 and 3.', 8: 'The last three digits must form a multiple of 8.',
        9: 'Add the digits. Their sum must be divisible by 9.', 10: 'The last digit must be 0.',
        11: 'Find the difference between the alternating digit sums. It must be 0 or a multiple of 11.',
        12: 'The number must be divisible by both 3 and 4.' };
    return game.type === 'archer' ? 'A divisor divides a number with no remainder. More than one target may be correct.'
        : `${rules[game.divisor] || 'Divide and check that there is no remainder.'}${game.smallest ? ' Choose the smallest valid digit. A number cannot start with zero.' : ''}`;
}
