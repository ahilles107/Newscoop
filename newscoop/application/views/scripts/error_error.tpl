{{extends file="layout.tpl"}}
{{block nav}}{{/block}}
{{block header}}{{/block}}
{{block footer}}{{/block}}

{{block title}}Error page{{/block}}

{{block content nocache}}
<div class="span error-page">
<h1>Hey, this is error!</h1>

<h2>{{ $message }}</h2>

{{ if isset($exception) }}
    <h3>Exception information</h3>
    <p>
        <b>Type:</b> {{ get_class($exception) }}
        <br />
        <b>Code:</b> {{ $exception->getCode() }}
        <br />
        <b>Message:</b> {{ $exception->getMessage() }}
        <br />
        <b>File:</b> {{ $exception->getFile() }} <b>:</b> {{ $exception->getLine() }}
    </p>

    <h3>Stack trace</h3>
    <pre>{{ $exception->getTraceAsString() }}</pre>
{{ /if }}
</div>
{{/block}}
