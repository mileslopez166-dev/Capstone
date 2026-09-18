document.addEventListener('assessment:graded', ({ detail }) => {
    const result = detail.phil_iri;
    document.querySelectorAll('[data-phil-iri-live]').forEach(container => {
        container.hidden = !result;
        if (!result) return;
        container.dataset.level = result.level || 'pending';
        const fields = {
            measure: result.measure, label: result.label,
            comprehension: result.comprehension_percent === null ? 'Not recorded' : `${result.correct_count} / ${result.question_count} (${result.comprehension_percent}%)`,
            comprehension_level: result.comprehension_level || '',
            interpretation_heading: result.assessment_type === 'group_screening' ? 'Screening interpretation' : 'Comprehension interpretation',
            comprehension_interpretation: result.comprehension_interpretation || '',
            word_reading: result.word_reading_percent === null ? 'Awaiting teacher scoring' : `${result.word_reading_percent}%`,
            word_reading_level: result.word_reading_level || '',
            miscues: result.miscues ?? 'Not recorded', rate: `${result.words_per_minute ?? ''} WPM`,
            marked_miscues: result.marked_miscues ?? '',
        };
        Object.entries(fields).forEach(([key, value]) => {
            container.querySelector(`[data-phil-field="${key}"]`).textContent = value;
        });
        container.querySelectorAll('[data-phil-oral]').forEach(row => { row.hidden = result.assessment_type !== 'oral_reading'; });
        container.querySelector('[data-phil-rate]').hidden = result.words_per_minute === null;
        container.querySelector('[data-phil-marked]').hidden = result.assessment_type !== 'oral_reading' || result.marked_miscues == null;
        container.querySelector('[data-phil-provisional]').hidden = !result.word_reading_provisional;
        container.querySelector('[data-phil-incomplete]').hidden = result.assessment_type !== 'oral_reading' || result.status !== 'incomplete';
        container.querySelector('[data-phil-gst]').hidden = result.assessment_type !== 'group_screening';
    });
});
