const percent = (value) => typeof value === 'number' && Number.isFinite(value) && value >= 0 && value <= 1
    ? `${Number((value * 100).toFixed(2))}%` : 'Not available';

document.addEventListener('assessment:graded', ({ detail }) => {
    const result = detail?.ml_prediction;
    document.querySelectorAll('[data-ml-live]').forEach(container => {
        container.hidden = false;
        container.querySelector('[data-ml-unavailable]').hidden = !!result;
        container.querySelector('[data-ml-details]').hidden = !result;
        const evaluation = result?.evaluation;
        const hasEvaluation = evaluation?.method === 'held_out_test'
            && Number.isInteger(evaluation.test_rows) && evaluation.test_rows > 0
            && percent(evaluation.accuracy) !== 'Not available';
        const fields = {
            prediction: result?.prediction ?? '',
            confidence: percent(result?.confidence),
            accuracy: hasEvaluation ? percent(evaluation.accuracy) : 'Not available',
            evaluation: hasEvaluation ? `Held-out test: ${evaluation.test_rows.toLocaleString('en-US')} records.`
                : 'No held-out test result was saved with this prediction.',
            recommendation: result?.recommendation ?? '',
        };
        Object.entries(fields).forEach(([name, value]) => {
            container.querySelector(`[data-ml-field="${name}"]`).textContent = value;
        });
    });
});
