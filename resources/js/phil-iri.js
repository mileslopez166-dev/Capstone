document.addEventListener('assessment:graded', ({ detail }) => {
    const result = detail.phil_iri;
    const formatPercent = (value) => {
        const number = Number(value);
        if (!Number.isFinite(number)) return '';
        return `${Number.isInteger(number) ? number : number.toFixed(2).replace(/0+$/, '').replace(/\.$/, '')}%`;
    };
    const formatNumber = (value) => {
        const number = Number(value);
        if (!Number.isFinite(number)) return '';
        return Number.isInteger(number) ? String(number) : number.toFixed(1).replace(/0+$/, '').replace(/\.$/, '');
    };
    const formatWhole = (value) => Number(value || 0).toLocaleString();
    const assessmentTypeLabel = (type) => ({
        oral_reading: 'Oral Reading Assessment',
        silent_reading: 'Silent Reading Assessment',
        listening_comprehension: 'Listening Comprehension Assessment',
        group_screening: 'Group Screening Test',
    })[type] || 'Literacy Assessment';
    const comprehensionFormulaLabel = (type) => ({
        listening_comprehension: 'Listening comprehension',
        group_screening: 'Screening score',
    })[type] || 'Reading comprehension';
    const formulaRows = (result) => {
        if (!result) return [];
        const rows = [];
        const resultQuestions = Number(result.question_count || 0);
        const resultCorrect = Number(result.correct_count || 0);
        const pointQuestions = Number(detail.question_count || 0);
        const pointCorrect = Number(detail.correct_count || 0);
        const wordCount = Number(result.word_count || 0);
        const miscues = Number(result.miscues || 0);
        const markedMiscues = Number(result.marked_miscues || 0);
        const readingSeconds = Number(result.reading_seconds || 0);
        const isOral = result.assessment_type === 'oral_reading';
        const hasWordReading = isOral && wordCount > 0 && result.word_reading_percent !== null && result.word_reading_percent !== undefined;

        if (resultQuestions > 0 && result.comprehension_percent !== null && result.comprehension_percent !== undefined) {
            rows.push([comprehensionFormulaLabel(result.assessment_type), `${resultCorrect} / ${resultQuestions} x 100 = ${formatPercent(result.comprehension_percent)}`]);
        }

        if (hasWordReading && result.reviewed_at && result.miscues !== null && result.miscues !== undefined) {
            rows.push(['Word reading', `(${wordCount} words - ${miscues} miscues) / ${wordCount} x 100 = ${formatPercent(result.word_reading_percent)}`]);
        } else if (hasWordReading && result.word_reading_source === 'red_marks' && result.marked_miscues !== null && result.marked_miscues !== undefined) {
            rows.push(['Word reading', `(${wordCount} words - ${markedMiscues} red-marked words) / ${wordCount} x 100 = ${formatPercent(result.word_reading_percent)}`]);
        }

        if (wordCount > 0 && readingSeconds > 0 && result.words_per_minute !== null && result.words_per_minute !== undefined) {
            rows.push(['Reading rate', `${wordCount} words / ${readingSeconds} seconds x 60 = ${formatNumber(result.words_per_minute)} WPM`]);
        }

        if (isOral && result.level && result.word_reading_level && result.comprehension_level) {
            rows.push(['Overall oral profile', `Lower of ${result.word_reading_level} word reading and ${result.comprehension_level} comprehension = ${result.level}`]);
        }

        if (result.assessment_type === 'group_screening' && resultQuestions === 20) {
            rows.push(['GST cutoff', `${resultCorrect} / 20 compared with 14 / 20 cutoff = ${result.label || 'Screening result'}`]);
        }

        if (pointQuestions > 0) {
            rows.push(['Assessment points', `${pointCorrect} correct x 250 = ${formatWhole(detail.points)} points`]);
            rows.push(['Possible points', `${pointQuestions} questions x 250 = ${formatWhole(detail.possible_points)} points`]);
        } else if (isOral && hasWordReading) {
            rows.push(['Assessment points', '0 question points; oral reading is scored through Phil-IRI.']);
        }

        return rows;
    };
    document.querySelectorAll('[data-phil-iri-live]').forEach(container => {
        container.hidden = !result;
        if (!result) return;
        container.dataset.level = result.level || 'pending';
        const fields = {
            assessment_type_label: result.assessment_type_label || assessmentTypeLabel(result.assessment_type),
            measure: result.measure, label: result.label,
            comprehension: result.comprehension_percent === null ? 'Not recorded' : `${result.correct_count} / ${result.question_count} (${result.comprehension_percent}%)`,
            comprehension_level: result.comprehension_level || '',
            interpretation_heading: result.assessment_type === 'group_screening' ? 'Screening interpretation' : 'Comprehension interpretation',
            comprehension_interpretation: result.comprehension_interpretation || '',
            word_reading: result.word_reading_percent === null ? 'Reading marks not recorded' : `${result.word_reading_percent}%`,
            word_reading_level: result.word_reading_level || '',
            miscues: result.miscues ?? 'Not recorded', rate: `${result.words_per_minute ?? ''} WPM`,
            marked_miscues: result.marked_miscues ?? '',
        };
        Object.entries(fields).forEach(([key, value]) => {
            const field = container.querySelector(`[data-phil-field="${key}"]`);
            if (field) field.textContent = value;
        });
        container.querySelectorAll('[data-phil-oral]').forEach(row => { row.hidden = result.assessment_type !== 'oral_reading'; });
        container.querySelector('[data-phil-rate]').hidden = result.words_per_minute === null;
        container.querySelector('[data-phil-marked]').hidden = result.assessment_type !== 'oral_reading' || result.marked_miscues == null;
        container.querySelector('[data-phil-provisional]').hidden = result.word_reading_source !== 'red_marks';
        container.querySelector('[data-phil-incomplete]').hidden = result.assessment_type !== 'oral_reading' || result.status !== 'incomplete';
        container.querySelector('[data-phil-gst]').hidden = result.assessment_type !== 'group_screening';
        const formula = container.querySelector('[data-phil-formula]');
        const list = container.querySelector('[data-phil-formula-list]');
        const rows = formulaRows(result);
        if (formula && list) {
            formula.hidden = rows.length === 0;
            list.replaceChildren(...rows.map(([label, value]) => {
                const row = document.createElement('div');
                const term = document.createElement('dt');
                const description = document.createElement('dd');
                term.textContent = label;
                description.textContent = value;
                row.append(term, description);
                return row;
            }));
        }
        const recommendation = result.practice_recommendation || null;
        const recommendationPanel = container.querySelector('[data-phil-recommendation]');
        if (recommendationPanel) {
            recommendationPanel.hidden = !recommendation;
            if (recommendation) {
                const values = {
                    recommendation_label: recommendation.label || 'Recommended next step',
                    recommendation_title: recommendation.title || '',
                    recommendation_summary: recommendation.summary || '',
                    recommendation_action: recommendation.action_label || 'Open practice missions',
                };
                Object.entries(values).forEach(([key, value]) => {
                    const field = recommendationPanel.querySelector(`[data-phil-field="${key}"]`);
                    if (field) field.textContent = value;
                });
                const steps = recommendationPanel.querySelector('[data-phil-recommendation-steps]');
                if (steps) {
                    steps.replaceChildren(...(recommendation.steps || []).map(text => {
                        const item = document.createElement('li');
                        item.textContent = text;
                        return item;
                    }));
                }
            }
        }
    });
});
