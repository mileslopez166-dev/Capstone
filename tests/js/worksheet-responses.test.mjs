import test from 'node:test';
import assert from 'node:assert/strict';
import { normalizeResponses, hasResponses } from '../../resources/js/worksheet-responses.js';
import { worksheetState, answeredParts } from '../../resources/js/worksheet-book.js';

const definitions = {
    '1-0': {fields:[{key:'divisors',type:'checkbox',options:['2','3','5']}]},
    '0-1': {fields:[{key:'choice',type:'radio',options:['Yes','No']},{key:'reason',type:'textarea'}]},
    '0-2': {fields:[{key:'drawing',type:'drawing'},{key:'answer',type:'number'}]},
};
test('fixed responses resume independently without replacing legacy answers',()=>{
    const state=worksheetState({pages:[{responses:{'1-0':{divisors:['2','5']}},answers:[{label:'old',answer:'saved'}]},{}]},2,[{responses:definitions},{}]);
    assert.deepEqual(state.pages[0].responses,{'1-0':{divisors:['2','5']}});
    assert.deepEqual(state.pages[1].responses,{});
    assert.equal(state.pages[0].answers[0].answer,'saved');
    assert.equal(answeredParts(state.pages),1);
});
test('untrusted backups cannot add questions, options, fields or malformed drawing points',()=>{
    assert.deepEqual(normalizeResponses({'9-9':{answer:'x'},'1-0':{divisors:['2','2','7'],score:100},'0-2':{drawing:[{color:'#174d97',width:3,points:[[NaN,0]]}]},'0-1':{choice:'Maybe'}},definitions),
        {'1-0':{divisors:['2']},'0-1':{choice:''},'0-2':{drawing:[]}});
});
test('zero and No count as responses; blank or unselected fields do not',()=>{
    assert.equal(hasResponses({'0-1':{choice:'',reason:'  '},'1-0':{divisors:[]}}),false);
    assert.equal(hasResponses({'0-1':{choice:'No'}}),true);
    assert.equal(hasResponses({'0-2':{answer:'0'}}),true);
});
test('input lengths and drawing counts are bounded',()=>{
    const ink={color:'#174d97',width:3,points:[[.1,.2],[.9,.8]]};
    const data=normalizeResponses({'0-1':{reason:'a'.repeat(3000)},'0-2':{drawing:Array(101).fill(ink),answer:'1'.repeat(500)}},definitions);
    assert.equal(data['0-1'].reason.length,2000);
    assert.equal(data['0-2'].drawing.length,100);
    assert.equal(data['0-2'].answer.length,200);
});
