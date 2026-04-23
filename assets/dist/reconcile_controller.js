import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = [ "reconcileAction", "acceptAction", "reconcileWith" ]
    
    connect() {
        // Initialize visibility and hidden input based on current state
        const checkedCheckbox = this.reconcileWithTargets.find(checkbox => checkbox.checked);
        const reconcileWith = checkedCheckbox ? checkedCheckbox.value : null;
        
        this.updateReconcileWithState(reconcileWith);
    }
    
    disconnect() {
        console.log('Reconcile controller disconnected');
    }
    
    select(event) {
        let reconcileWith = event.target.checked ? event.target.value : null;

        this.reconcileWithTargets.forEach(checkbox => {
            if (checkbox !== event.target) {
                checkbox.checked = false;
            }
        });

        this.updateReconcileWithState(reconcileWith);
    }
    
    updateReconcileWithState(reconcileWith) {
        // Create or update hidden input with reconcileWith value
        let input = this.reconcileActionTarget.querySelector('input[name="reconcileWith"]');
        if (!input) {
            input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'reconcileWith';
            this.reconcileActionTarget.appendChild(input);
        }
        input.value = reconcileWith || '';
        
        // Hide/show reconcile/accept action
        this.reconcileActionTarget.hidden = !reconcileWith;
        this.acceptActionTarget.hidden = reconcileWith;
        this.reconcileActionTarget.closest('form').hidden = !reconcileWith;
        this.acceptActionTarget.closest('form').hidden = reconcileWith;
    }
}
