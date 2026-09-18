<dialog class="worksheet-table-dialog" data-book-table-dialog aria-labelledby="worksheet-table-heading">
    <header><h2 id="worksheet-table-heading">Multiplication Table</h2><button type="button" class="worksheet-icon" data-book-table-close aria-label="Close multiplication table" title="Close multiplication table"><span class="material-symbols-outlined" aria-hidden="true">close</span></button></header>
    <form data-book-table-form autocomplete="off">
        <p class="worksheet-approval-title"><span class="material-symbols-outlined" aria-hidden="true">lock</span>Teacher approval required</p>
        <label for="worksheet-teacher-password">Assessment teacher's password</label>
        <input type="password" name="password" id="worksheet-teacher-password" data-book-table-password required maxlength="1024" autocomplete="off">
        <button type="submit" class="ui-button" data-book-table-unlock><span class="material-symbols-outlined" aria-hidden="true">lock_open</span>Allow This Attempt</button>
    </form>
    <p data-book-table-status role="status" aria-live="polite"></p>
    <div class="worksheet-table-scroll" data-book-table-content tabindex="0" aria-label="Multiplication table" hidden></div>
</dialog>
