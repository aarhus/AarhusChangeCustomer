@php
    // FreeScout stores dotted option names as literal keys inside settings.
    $oldSettings = old('settings', []);
    $oldSettings = is_array($oldSettings) ? $oldSettings : [];
    $active = $oldSettings['aarhuschangecustomer.active'] ?? ($settings['aarhuschangecustomer.active'] ?? 'off');
    $ruleset = $oldSettings['aarhuschangecustomer.ruleset'] ?? ($settings['aarhuschangecustomer.ruleset'] ?? '');
    if (is_array($ruleset)) {
        $ruleset = json_encode($ruleset, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    }
    $ruleset = is_string($ruleset) ? $ruleset : '';
@endphp
<form class="form-horizontal margin-top margin-bottom" method="POST" action="" id="aarhuschangecustomer_form">
    {{ csrf_field() }}

    <div class="form-group{{ $errors->has('settings.aarhuschangecustomer.active') ? ' has-error' : '' }} margin-bottom-10">
        <label for="aarhuschangecustomer.active" class="col-sm-2 control-label">{{ __('Active') }}</label>
        <div class="col-sm-6">
            <input type="hidden" name="settings[aarhuschangecustomer.active]" value="off" />
            <input id="aarhuschangecustomer.active" type="checkbox" value="on"
                   name="settings[aarhuschangecustomer.active]"
                   @if ($active === 'on') checked="checked" @endif
            />
        </div>
    </div>

    <div class="form-group{{ $errors->has('settings.aarhuschangecustomer.ruleset') ? ' has-error' : '' }} margin-bottom-10">
        <label for="aarhuschangecustomer.ruleset" class="col-sm-2 control-label">{{ __('Ruleset JSON') }}</label>
        <div class="col-sm-6">
            <textarea id="aarhuschangecustomer.ruleset" rows="30" cols="50" class="form-control input-sized-lg"
                      name="settings[aarhuschangecustomer.ruleset]">{{ $ruleset }}</textarea>
            @if ($errors->has('settings.aarhuschangecustomer.ruleset'))
                <span class="help-block">{{ $errors->first('settings.aarhuschangecustomer.ruleset') }}</span>
            @endif
        </div>
    </div>

    <div class="form-group margin-top margin-bottom">
        <div class="col-sm-6 col-sm-offset-2">
            <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
        </div>
    </div>
</form>
