// Frontend Test Suite
class FrontendTest {
    constructor() {
        this.testResults = [];
        this.baseUrl = 'http://localhost/2ddd';
    }

    log(testName, result, message = '') {
        const status = result ? '✓ PASS' : '✗ FAIL';
        const logEntry = `[${status}] ${testName}${message ? ': ' + message : ''}`;
        this.testResults.push({ name: testName, status, message });
        console.log(logEntry);
    }

    async testDOMElements() {
        console.log('\nTesting DOM Elements...');
        
        // Test navigation elements
        const navElements = document.querySelectorAll('nav a');
        this.log(
            'Navigation Menu',
            navElements.length > 0,
            `Found ${navElements.length} navigation items`
        );

        // Test form elements
        const forms = document.querySelectorAll('form');
        this.log(
            'Form Elements',
            forms.length > 0,
            `Found ${forms.length} forms`
        );

        // Test input fields
        const inputs = document.querySelectorAll('input');
        this.log(
            'Input Fields',
            inputs.length > 0,
            `Found ${inputs.length} input fields`
        );

        // Test buttons
        const buttons = document.querySelectorAll('button');
        this.log(
            'Button Elements',
            buttons.length > 0,
            `Found ${buttons.length} buttons`
        );
    }

    async testResponsiveness() {
        console.log('\nTesting Responsiveness...');
        
        const viewports = [
            { width: 320, height: 568, name: 'Mobile' },
            { width: 768, height: 1024, name: 'Tablet' },
            { width: 1366, height: 768, name: 'Desktop' }
        ];

        for (const viewport of viewports) {
            // Simulate viewport resize
            window.innerWidth = viewport.width;
            window.innerHeight = viewport.height;
            window.dispatchEvent(new Event('resize'));

            // Check responsive elements
            const container = document.querySelector('.container');
            const isResponsive = container && 
                               window.getComputedStyle(container).maxWidth !== 'none';

            this.log(
                `${viewport.name} Responsiveness`,
                isResponsive,
                `Viewport: ${viewport.width}x${viewport.height}`
            );
        }
    }

    async testFormValidation() {
        console.log('\nTesting Form Validation...');

        // Test login form
        const loginForm = document.querySelector('#loginForm');
        if (loginForm) {
            // Test empty submission
            const emptySubmit = await this.simulateFormSubmit(loginForm, {});
            this.log(
                'Login Form Empty Validation',
                !emptySubmit,
                'Form should not submit with empty fields'
            );

            // Test invalid phone number
            const invalidPhone = await this.simulateFormSubmit(loginForm, {
                phone_number: '123',
                password: 'test123'
            });
            this.log(
                'Login Form Phone Validation',
                !invalidPhone,
                'Form should not submit with invalid phone number'
            );

            // Test valid submission
            const validSubmit = await this.simulateFormSubmit(loginForm, {
                phone_number: '123456789',
                password: 'test123'
            });
            this.log(
                'Login Form Valid Submission',
                validSubmit,
                'Form should submit with valid data'
            );
        }
    }

    async testAPIIntegration() {
        console.log('\nTesting API Integration...');

        // Test login API
        try {
            const loginResponse = await fetch(`${this.baseUrl}/api/agent/login.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    phone_number: '123456789',
                    password: 'test123'
                })
            });

            this.log(
                'Login API Integration',
                loginResponse.ok,
                `Status: ${loginResponse.status}`
            );

            if (loginResponse.ok) {
                const data = await loginResponse.json();
                localStorage.setItem('token', data.token);
            }
        } catch (error) {
            this.log('Login API Integration', false, error.message);
        }

        // Test protected API endpoints
        const token = localStorage.getItem('token');
        if (token) {
            try {
                const response = await fetch(`${this.baseUrl}/api/agent/transactions.php`, {
                    headers: {
                        'Authorization': `Bearer ${token}`
                    }
                });

                this.log(
                    'Protected API Integration',
                    response.ok,
                    `Status: ${response.status}`
                );
            } catch (error) {
                this.log('Protected API Integration', false, error.message);
            }
        }
    }

    async testUserInteractions() {
        console.log('\nTesting User Interactions...');

        // Test button clicks
        const buttons = document.querySelectorAll('button');
        for (const button of buttons) {
            try {
                const clickEvent = new MouseEvent('click', {
                    bubbles: true,
                    cancelable: true
                });
                button.dispatchEvent(clickEvent);

                this.log(
                    `Button Click: ${button.textContent}`,
                    true,
                    'Click event dispatched'
                );
            } catch (error) {
                this.log(
                    `Button Click: ${button.textContent}`,
                    false,
                    error.message
                );
            }
        }

        // Test form inputs
        const inputs = document.querySelectorAll('input');
        for (const input of inputs) {
            try {
                input.value = 'Test Value';
                input.dispatchEvent(new Event('input'));

                this.log(
                    `Input Interaction: ${input.name || input.id}`,
                    input.value === 'Test Value',
                    'Input value updated'
                );
            } catch (error) {
                this.log(
                    `Input Interaction: ${input.name || input.id}`,
                    false,
                    error.message
                );
            }
        }
    }

    async simulateFormSubmit(form, data) {
        // Fill form fields
        for (const [key, value] of Object.entries(data)) {
            const input = form.querySelector(`[name="${key}"]`);
            if (input) {
                input.value = value;
                input.dispatchEvent(new Event('input'));
            }
        }

        // Trigger form submission
        const submitEvent = new Event('submit', {
            bubbles: true,
            cancelable: true
        });
        
        form.dispatchEvent(submitEvent);
        return !submitEvent.defaultPrevented;
    }

    async runTests() {
        console.log('Starting Frontend Tests...');

        await this.testDOMElements();
        await this.testResponsiveness();
        await this.testFormValidation();
        await this.testAPIIntegration();
        await this.testUserInteractions();

        console.log('\nTest Summary:');
        const total = this.testResults.length;
        const passed = this.testResults.filter(test => 
            test.status.includes('PASS')
        ).length;

        console.log(`Total Tests: ${total}`);
        console.log(`Passed: ${passed}`);
        console.log(`Failed: ${total - passed}`);
    }
}

// Run tests when DOM is ready
document.addEventListener('DOMContentLoaded', async () => {
    const tester = new FrontendTest();
    await tester.runTests();
});
