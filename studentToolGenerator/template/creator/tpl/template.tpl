<div{{#if id}} id="{{id}}"{{/if}} class="sts-container sts-{{type}}-container{{#if is.movable}} sts-movable-container{{/if}}{{#if is.transparent}} sts-transparent-container{{/if}}">
    <div class="sts-title-bar">
        <div class="sts-title">{{title}}</div>
        <ul class="sts-header-controls">
            <li class="sts-close sts-button"></li>
        </ul>
    </div>
    <div class="sts-content">
    <!-- The template for {client}/{tool-title} goes here -->
    </div>
    {{#if is.transmutable}}
    <div class="sts-container-controls">
        {{#each is.rotatable}}
            {{#if this}}
                <div class="sts-handle-rotate-{{@key}}"></div>
            {{/if}}
        {{/each}}
        {{#each is.adjustable}}
            {{#if this}}
                <div class="sts-handle-adjustable-{{@key}}"></div>
            {{/if}}
        {{/each}}
    </div>
    {{/if}}
</div>