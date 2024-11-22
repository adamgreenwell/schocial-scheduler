import { createRoot } from '@wordpress/element';
import SettingsPage from './components/SettingsPage';

console.log( 'Settings page initializing...' );

const rootElement = document.getElementById( 'schocial-settings-root' );
if ( rootElement ) {
	const root = createRoot( rootElement );
	root.render( <SettingsPage /> );
} else {
	console.error( 'Could not find settings root element' );
}
