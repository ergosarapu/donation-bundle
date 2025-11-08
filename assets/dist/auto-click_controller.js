import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static values = {
        timeout: { type: Number, default: 0 }
    }

    connect() {
        setTimeout(() => {
            this.element.click();
        }, this.timeoutValue);
    }
}
