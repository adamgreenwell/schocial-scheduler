const { createElement, Fragment } = window.wp.element;
const { useState, useEffect } = window.wp.element;
const { Notice } = window.wp.components;
const { __ } = window.wp.i18n;

const LinkedInTokenStatus = ( { tokenExpiresTimestamp } ) => {
	const [ timeRemaining, setTimeRemaining ] = useState( '' );
	const [ status, setStatus ] = useState( 'success' ); // success, warning, error

	useEffect( () => {
		const calculateStatus = () => {
			if ( ! tokenExpiresTimestamp ) {
				return;
			}

			const now = Math.floor( Date.now() / 1000 );
			const expiresIn = tokenExpiresTimestamp - now;
			const daysRemaining = Math.floor( expiresIn / 86400 );

			if ( daysRemaining < 0 ) {
				setTimeRemaining( __( 'Token expired' ) );
				setStatus( 'error' );
			} else if ( daysRemaining === 0 ) {
				setTimeRemaining( __( 'Token expires today' ) );
				setStatus( 'warning' );
			} else if ( daysRemaining === 1 ) {
				setTimeRemaining( __( 'Token expires tomorrow' ) );
				setStatus( 'warning' );
			} else {
				// translators: %d: Days Remaining
				setTimeRemaining( __( 'Token expires in %d days' ), daysRemaining );
				setStatus( daysRemaining <= 7 ? 'warning' : 'success' );
			}
		};

		calculateStatus();
		const interval = setInterval( calculateStatus, 3600000 ); // Update every hour
		return () => clearInterval( interval );
	}, [ tokenExpiresTimestamp ] );

	if ( ! tokenExpiresTimestamp ) {
		return null;
	}

	return createElement(
		Fragment,
		null,
		createElement(
			Notice,
			{
				status,
				isDismissible: false,
				className: 'linkedin-token-status',
			},
			[
				createElement( 'strong', { key: 'title' }, __( 'LinkedIn Authentication Status:' ) ),
				createElement(
					'span',
					{
						key: 'time',
						style: { marginLeft: '8px' },
					},
					timeRemaining
				),
				status !== 'success' &&
					createElement(
						'p',
						{
							key: 'warning',
							style: { marginTop: '8px', marginBottom: '0' },
						},
						__(
							'Please reconnect your LinkedIn account before the token expires to ensure uninterrupted posting.'
						)
					),
			]
		)
	);
};

export default LinkedInTokenStatus;
