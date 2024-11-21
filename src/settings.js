import { render } from '@wordpress/element';
import SettingsPage from './components/SettingsPage';

console.log('Settings page initializing...');

// Add error boundary
const rootElement = document.getElementById('schocial-settings-root');
if (rootElement) {
    render(<SettingsPage />, rootElement);
} else {
    console.error('Could not find settings root element');
}