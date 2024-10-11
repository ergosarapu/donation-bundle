import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = [ "focusMe" ]

    focus() {
        this.focusMeTarget.focus();
    }
}
