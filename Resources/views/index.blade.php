<form class="form-horizontal margin-top margin-bottom" method="POST" action="" id="aarhuschangecustomer_form">
    {{ csrf_field() }}

    <div class="form-group{{ $errors->has('settings.aarhuschangecustomer.active') ? ' has-error' : '' }} margin-bottom-10">
        <label for="aarhuschangecustomer.active" class="col-sm-2 control-label">{{ __('Active') }}</label>

        <div class="col-sm-6">
            <input id="aarhuschangecustomer.active" type="checkbox" class=""
                   name="settings[aarhuschangecustomer.active]"
                   @if (old('settings[aarhuschangecustomer.active]', $settings['aarhuschangecustomer.active']) == 'on') checked="checked" @endif
            />
        </div>
    </div>

    <div class="form-group{{ $errors->has('settings.aarhuschangecustomer.ruleset') ? ' has-error' : '' }} margin-bottom-10">
        <label for="aarhuschangecustomer.ruleset" class="col-sm-2 control-label">{{ __('Ruleset JSON') }}</label>

        <div class="col-sm-6">
            <textarea id="aarhuschangecustomer.client_id" type="text" rows="30" cols="50" class="form-control input-sized-lg"
                   name="settings[aarhuschangecustomer.ruleset]">{{ old('settings.aarhuschangecustomer.ruleset', $settings['aarhuschangecustomer.ruleset']) }}</textarea>
        </div>
    </div>

    <div class="form-group margin-top margin-bottom">
        <div class="col-sm-6 col-sm-offset-2">
            <button type="submit" class="btn btn-primary">
                {{ __('Save') }}
            </button>
        </div>
    </div>
</form>
