import test from 'node:test';
import assert from 'node:assert/strict';
import {documentDraft,calendarExport} from '../../public/js/core/documents.js';
import {sampleStore} from '../../public/js/core/domain.js';
test('Drafts include accepted scope and keep unresolved terms visible',()=>{const c=sampleStore().conversations[1];c.analysis.terms[0].status='accepted';const sow=documentDraft('Statement of work',c);assert.match(sow,/DELIVERABLES & SCOPE/);assert.match(sow,/OPEN QUESTIONS/);assert.match(sow,/not signed/);assert.match(documentDraft('Follow-up email',c),/^Subject:/);assert.match(documentDraft('Change request',c),/COST & TIMELINE IMPACT/);});
test('Calendar export excludes undated tasks and escapes text',()=>{const tasks=[{id:'a',title:'Design, review',due:'2026-10-16',status:'todo',conversationId:'c',note:'line one\nline two'},{id:'b',title:'Undated',due:'',status:'todo',conversationId:'c',note:''}];const ics=calendarExport(tasks);assert.match(ics,/DTSTART;VALUE=DATE:20261016/);assert.match(ics,/Design\\, review/);assert.match(ics,/line one\\nline two/);assert.doesNotMatch(ics,/Undated/);});
