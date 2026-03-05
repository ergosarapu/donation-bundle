import { Controller } from '@hotwired/stimulus';

export default class extends Controller {

    connect() {
    }

    startPolling(cmdCorIds) {
        const pollInterval = setInterval(async () => {
            try {
                const queryString = cmdCorIds.map(id => `ids[]=${encodeURIComponent(id)}`).join('&');
                const response = await fetch(`/api/command-status?${queryString}`);
                
                if (!response.ok) {
                    console.error('Failed to fetch command status:', response.status);
                    return;
                }
                
                const data = await response.json();
                
                console.log('Polling response:', data.length, 'of', cmdCorIds.length);
                
                if (Array.isArray(data) && data.length === cmdCorIds.length) {
                    clearInterval(pollInterval);
                    clearTimeout(pollTimeout);
                    window.location.reload();
                }
            } catch (error) {
                console.error('Error polling command status:', error);
            }
        }, 100);

        const pollTimeout = setTimeout(() => {
            clearInterval(pollInterval);
            console.error('Polling timeout: commands did not complete within 3 seconds');
            window.location.reload();
        }, 3000);
    }

    async intercept(event) {
        console.log('Event params:', event.params);

        event.preventDefault();
        
        if (event.params.confirmation) {
            const confirmed = window.confirm(event.params.confirmation);
            if (!confirmed) {
                console.log('User cancelled the action');
                return;
            }
        }
        
        const button = event.target instanceof HTMLButtonElement 
            ? event.target 
            : event.target.closest('button');
        if (button) {
            button.disabled = true;
            const icon = button.querySelector('.btn-icon i');
            if (icon) {
                icon.className = 'fa fa-spinner';
            }
        }

        const form = event.target instanceof HTMLFormElement 
            ? event.target 
            : event.target.closest('form');
        
        if (form) {
            const formData = new FormData(form);
            const formDataObj = Object.fromEntries(formData.entries());
            
            console.log('Prevented form submission:', {
                action: form.action,
                method: form.method,
                formData: formDataObj
            });
                        
            const response = await fetch(form.action, {
                method: form.method || 'POST',
                body: formData,
            });
            
            console.log('AJAX response status:', response.status);
            
            const contentType = response.headers.get('content-type');
            let data;
            
            if (contentType && contentType.includes('application/json')) {
                data = await response.json();
            } else {
                data = await response.text();
            }
                        
            if (response.ok) {
                console.log('Form submitted successfully via AJAX');
            } else {
                console.error('Form submission failed:', response.status, data);
            }

            this.startPolling([data.correlationId]);
        } else {
            console.log('Intercepted event on non-form element:', event.target);
        }
    }
}
