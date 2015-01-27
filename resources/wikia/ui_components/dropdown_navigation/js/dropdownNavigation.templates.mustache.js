define( 'wikia.dropdownNavigation.templates', [], function() { 'use strict'; return {
    "dropdown" : '<div class="wikia-dropdown-nav-wrapper" id="{{id}}"><div class="wikia-dropdown-nav-sections-wrapper"><div class="wikia-dropdown-nav-inner-wrapper"><ul class="wikia-dropdown-nav{{#classes}} {{.}}{{/classes}}">{{#sections}}{{>dropdownItem}}{{/sections}}</ul></div></div><div class="wikia-dropdown-nav-subsections-wrapper"><div class="wikia-dropdown-nav-inner-wrapper">{{#subsections}}<ul id="{{referenceId}}" class="wikia-dropdown-nav">{{#sections}}{{>dropdownItem}}{{/sections}}</ul>{{/subsections}}</div></div></div>',
    "dropdownItem" : '<li {{#referenceId}}data-id="{{referenceId}}" {{/referenceId}} class="wikia-dropdown-nav-item"><a href="{{href}}" title="{{#tooltip}}{{tooltip}}{{/tooltip}}{{^tooltip}}{{title}}{{/tooltip}}"{{#accesskey}}accesskey="{{accesskey}}" {{/accesskey}}{{#id}}id="{{id}}" {{/id}}{{#id}}data-tracking-id="{{id}}" {{/id}}rel="{{#rel}}{{rel}}{{/rel}}{{^rel}}nofollow{{/rel}}"{{#dataAttr}}data-{{key}}="{{value}}"{{/dataAttr}}{{#class}} class="{{class}}" {{/class}}>{{#rawTitle}}{{{title}}}{{/rawTitle}}{{^rawTitle}}{{title}}{{/rawTitle}}</a></li>',
    "done": "true"
  }; });
