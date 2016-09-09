<div class="keychecker">

    <div class="section shortcuts hidden">
        <div class="info">
            <div class="platform">
                <label>Platform</label>
                <span class="value"></span>
            </div>
            <div class="browser">
                <label>Browser</label>
                <span class="value"></span>
            </div>
        </div>

        <div class="controls">
            <button class="reset" data-control="resetAll">Reset</button>
        </div>

        <div class="desk">
            <div class="check">
                <div class="key">
                    <label>Please hit this shortcut:</label>
                    <span class="value"></span>
                </div>

                <div class="description">
                    <label>What this shortcut usually does:</label>
                    <span class="value"></span>
                </div>

                <div class="playground">
                    <label>You can use this playground to test the shortcut:</label>
                    <div class="content">
                        <textarea>The quick brown fox jumps over the lazy dog</textarea>
                        <div class="links">
                            <a href="http://www.taotesting.com">www.taotesting.com</a><br>
                            <a href="http://www.google.com">www.google.com</a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="result">
                <label>Once the shortcut has been activated:</label>
                <div class="content">
                    <div class="flags">
                        <div class="caught">
                            <label>The shortcut has been caught by the script:</label>
                            <span class="value"></span>
                        </div>
                        <div class="prevented">
                            <label>Please tell if the default behavior of the shortcut has been prevented:</label>
                            <span class="value">
                                <input type="radio" name="default_prevented" id="default_prevented_yes" value="1"/><label
                                    for="default_prevented_yes">Prevented, nothing happen!</label>
                            </span>
                            <span class="value">
                                <input type="radio" name="default_prevented" id="default_prevented_no" value="0"/><label
                                    for="default_prevented_no">The browser has done something...</label>
                            </span>
                        </div>
                    </div>
                    <div class="comment">
                        <label>You can write some comments about this shortcut:</label>
                        <span class="value"><textarea></textarea></span>
                    </div>
                </div>
            </div>

            <div class="controls">
                <button class="previous" data-control="previousShortcut">Previous</button>
                <button class="next" data-control="nextShortcut">Next</button>
            </div>
        </div>

        <div class="end">
            No more keys to test!
        </div>
    </div>

    <div class="section results hidden">
        <div class="controls">
            <button class="select" data-control="selectResults">Select and copy to clipboard</button>
        </div>

        <pre></pre>
    </div>

</div>
