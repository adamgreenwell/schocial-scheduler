import LinkedInTokenStatus from './LinkedInTokenStatus';

const { useState, useEffect } = window.wp.element;

const { createElement, Fragment } = window.wp.element;
const apiFetch = window.wp.apiFetch;
const { __ } = window.wp.i18n;

const TestPostButton = ( { platform } ) => {
	const [ isLoading, setIsLoading ] = useState( false );
	const [ result, setResult ] = useState( null );
	const Button = window.wp.components.Button;
	const Notice = window.wp.components.Notice;

	const handleTest = async () => {
		setIsLoading( true );
		try {
			const posts = await apiFetch( { path: '/wp/v2/posts?per_page=1' } );
			if ( ! posts || posts.length === 0 ) {
				throw new Error( 'No posts found to test with' );
			}

			const response = await apiFetch( {
				path: `/schocial/v1/test-post/${ platform }/${ posts[ 0 ].id }`,
				method: 'POST',
			} );
			setResult( response );
		} catch ( error ) {
			setResult( {
				success: false,
				message: error.message,
			} );
		}
		setIsLoading( false );
	};

	return createElement(
		'div',
		{
			key: `test-button-${ platform }-container`,
			style: { marginTop: '10px' },
		},
		[
			createElement(
				Button,
				{
					key: `test-button-${ platform }`,
					isPrimary: false,
					onClick: handleTest,
					isBusy: isLoading,
				},
				`${ __( 'Test' ) } ${ platform } ${ __( 'Post' ) }`
			),
			result &&
				createElement(
					Notice,
					{
						key: `test-notice-${ platform }`,
						status: result.success ? 'success' : 'error',
						isDismissible: true,
						onRemove: () => setResult( null ),
						style: { marginTop: '10px' },
					},
					result.message
				),
		].filter( Boolean )
	); // Filter out null/undefined elements
};

const SettingsPage = () => {
	const [ settings, setSettings ] = useState( {
		facebook_api_key: '',
		facebook_page_id: '',
		twitter_api_key: '',
		twitter_api_secret: '',
		twitter_bearer_token: '',
		twitter_access_token: '',
		twitter_access_token_secret: '',
		twitter_callback_url: '',
		linkedin_client_id: '',
		linkedin_client_secret: '',
		instagram_api_key: '',
		enabled_platforms: {
			facebook: true,
			twitter: true,
			linkedin: true,
			instagram: true,
		},
		twitter_settings: {
			auto_thread: false,
			append_link: true,
			include_title: true,
			include_featured_image: false,
			thread_length: 280,
		},
	} );
	const [ isSaving, setIsSaving ] = useState( false );

	const [ showFacebookKeys, setShowFacebookKeys ] = useState( false );
	const [ showTwitterKeys, setShowTwitterKeys ] = useState( false );
	const [ showLinkedinKeys, setShowLinkedinKeys ] = useState( false );
	const [ showInstagramKeys, setShowInstagramKeys ] = useState( false );

	const [ twitterValidation, setTwitterValidation ] = useState( null );

	const [ linkedinValidation, setLinkedinValidation ] = useState( null );

	const validateLinkedinCredentials = async () => {
		try {
			setLinkedinValidation( null );
			const response = await apiFetch( {
				path: '/schocial/v1/validate-linkedin-credentials',
				method: 'POST',
			} );

			console.log( 'LinkedIn validation response:', response );

			if ( response.authUrl ) {
				console.log( 'Opening auth URL:', response.authUrl );
				const authWindow = window.open(
					response.authUrl,
					'LinkedIn Authorization',
					'width=600,height=600,menubar=no,toolbar=no,location=no,status=no'
				);

				if ( ! authWindow ) {
					throw new Error( __( 'Popup blocked. Please allow popups for this site.' ) );
				}
			}

			setLinkedinValidation( {
				success: true,
				message: response.message,
			} );
		} catch ( error ) {
			console.error( 'LinkedIn validation error:', error );
			setLinkedinValidation( {
				success: false,
				message: error.message || __( 'Failed to validate LinkedIn credentials' ),
			} );
		}
	};

	// Set up API fetch with nonce middleware
	useEffect( () => {
		if ( window?.schocialSettings?.restNonce ) {
			apiFetch.use( apiFetch.createNonceMiddleware( window.schocialSettings.restNonce ) );
		}
		// Add the custom endpoints middleware
		apiFetch.use( apiFetch.createRootURLMiddleware( window.schocialSettings.restUrl ) );
	}, [] );

	// Get WordPress components
	const Card = window.wp.components.Card;
	const CardBody = window.wp.components.CardBody;
	const CardHeader = window.wp.components.CardHeader;
	const CheckboxControl = window.wp.components.CheckboxControl;
	const TextControl = window.wp.components.TextControl;
	const Button = window.wp.components.Button;
	const Notice = window.wp.components.Notice;

	const platforms = [
		{ id: 'facebook', label: 'Facebook' },
		{ id: 'twitter', label: 'X (Twitter)' },
		{
			id: 'linkedin',
			label: 'LinkedIn',
		},
		{ id: 'instagram', label: 'Instagram' },
	];

	useEffect( () => {
		apiFetch( { path: '/schocial/v1/settings' } ).then( response => {
			setSettings( response );
		} );
	}, [] );

	const handleSave = async () => {
		setIsSaving( true );
		try {
			const response = await apiFetch( {
				path: '/schocial/v1/settings',
				method: 'POST',
				data: settings,
			} );
			setSettings( response );

			// Validate Twitter credentials after saving if they're provided
			if ( settings.twitter_api_key && settings.twitter_bearer_token ) {
				validateTwitterCredentials();
			}
		} catch ( error ) {
			console.error( 'Failed to save settings:', error );
		}
		setIsSaving( false );
	};

	const validateTwitterCredentials = async () => {
		try {
			setTwitterValidation( null ); // Reset any previous validation state

			console.log(
				'Sending Twitter validation request with token:',
				settings.twitter_bearer_token
			);

			if ( ! window?.schocialSettings?.restNonce ) {
				console.error( 'REST nonce not found in schocialSettings' );
				throw new Error( __( 'Security token not found. Please refresh the page.' ) );
			}

			const response = await apiFetch( {
				path: '/schocial/v1/validate-twitter-credentials',
				method: 'POST',
				data: {
					api_key: settings.twitter_api_key || '',
					api_secret: settings.twitter_api_secret || '',
					bearer_token: settings.twitter_bearer_token || '',
				},
				headers: {
					'X-WP-Nonce': window.schocialSettings.restNonce,
					'Content-Type': 'application/json',
				},
			} );

			console.log( 'Twitter validation response:', response );

			setTwitterValidation( {
				success: true,
				message: response.message || __( 'Twitter credentials validated successfully!' ),
			} );
		} catch ( error ) {
			console.error( 'Twitter validation error:', error );

			let errorMessage;
			if ( error.code === 'rest_forbidden' ) {
				errorMessage = __( 'Permission denied. Please refresh the page and try again.' );
			} else if ( error.data?.message ) {
				errorMessage = error.data.message;
			} else {
				errorMessage = error.message || __( 'Failed to validate Twitter credentials' );
			}

			setTwitterValidation( {
				success: false,
				message: errorMessage,
			} );
		}
	};

	return createElement( Fragment, null, [
		createElement( Card, { key: 'enabled-platforms-card' }, [
			createElement(
				CardHeader,
				{ key: 'enabled-platforms-header' },
				createElement( 'h2', { key: 'enabled-platforms-title' }, __( 'Enabled Platforms' ) )
			),
			createElement( CardBody, { key: 'enabled-platforms-body' }, [
				...platforms.map( platform =>
					createElement( CheckboxControl, {
						key: `${ platform.id }-checkbox`,
						label: platform.label,
						checked: settings.enabled_platforms?.[ platform.id ],
						onChange: value =>
							setSettings( {
								...settings,
								enabled_platforms: {
									...settings.enabled_platforms,
									[ platform.id ]: value,
								},
							} ),
					} )
				),
				createElement(
					Button,
					{
						key: 'save-button-1',
						isPrimary: true,
						onClick: handleSave,
						isBusy: isSaving,
					},
					__( 'Save Settings' )
				),
			] ),
		] ),
		createElement( Card, { key: 'api-keys-card' }, [
			createElement(
				CardHeader,
				{ key: 'api-keys-header' },
				createElement( 'h2', { key: 'api-keys-title' }, __( 'Social Media API Keys' ) )
			),
			createElement( CardBody, { key: 'api-keys-body' }, [
				createElement(
					'div',
					{
						key: 'facebook-group',
						style: { marginBottom: '20px' },
					},
					[
						createElement(
							Button,
							{
								key: 'toggle-facebook-keys',
								variant: 'secondary',
								onClick: () => setShowFacebookKeys( ! showFacebookKeys ),
								style: { marginBottom: '10px' },
							},
							showFacebookKeys ? __( 'Hide Facebook API Keys' ) : __( 'Show Facebook API Keys' )
						),
						showFacebookKeys && [
							createElement( TextControl, {
								key: 'facebook-api-input',
								label: 'Facebook Page Access Token',
								value: settings.facebook_api_key,
								onChange: value =>
									setSettings( {
										...settings,
										facebook_api_key: value,
									} ),
							} ),
							createElement( TextControl, {
								key: 'facebook-page-id-input',
								label: 'Facebook Page ID',
								value: settings.facebook_page_id,
								onChange: value =>
									setSettings( {
										...settings,
										facebook_page_id: value,
									} ),
								help: __( 'Enter your Facebook Page ID to post as your Page' ),
							} ),
						],
						createElement( TestPostButton, {
							key: 'facebook-test',
							platform: 'facebook',
						} ),
					]
				),
				createElement( 'div', { key: 'twitter-group', style: { marginBottom: '20px' } }, [
					createElement(
						Button,
						{
							key: 'toggle-twitter-keys',
							variant: 'secondary',
							onClick: () => setShowTwitterKeys( ! showTwitterKeys ),
							style: { marginBottom: '10px' },
						},
						showTwitterKeys ? __( 'Hide Twitter API Keys' ) : __( 'Show Twitter API Keys' )
					),
					showTwitterKeys && [
						createElement( TextControl, {
							key: 'twitter-api-key-input',
							label: 'Twitter API Key',
							value: settings.twitter_api_key || '',
							onChange: value =>
								setSettings( {
									...settings,
									twitter_api_key: value,
								} ),
							help: __( 'Your Twitter API Key from the Developer Portal' ),
						} ),
						createElement( TextControl, {
							key: 'twitter-api-secret-input',
							label: 'Twitter API Secret',
							value: settings.twitter_api_secret || '',
							onChange: value =>
								setSettings( {
									...settings,
									twitter_api_secret: value,
								} ),
							type: 'password',
							help: __( 'Your Twitter API Secret from the Developer Portal' ),
						} ),
						createElement( TextControl, {
							key: 'twitter-bearer-token-input',
							label: 'Twitter Bearer Token',
							value: settings.twitter_bearer_token || '',
							onChange: value =>
								setSettings( {
									...settings,
									twitter_bearer_token: value,
								} ),
							type: 'password',
							help: __( 'Your Twitter Bearer Token for OAuth 2.0' ),
						} ),
						createElement(
							Button,
							{
								key: 'validate-twitter-button',
								variant: 'secondary',
								onClick: validateTwitterCredentials,
								style: { marginTop: '10px' },
							},
							__( 'Validate Twitter Credentials' )
						),
						twitterValidation &&
							createElement(
								Notice,
								{
									key: 'twitter-validation-notice',
									status: twitterValidation.success ? 'success' : 'error',
									isDismissible: true,
									onRemove: () => setTwitterValidation( null ),
									style: { marginTop: '10px' },
								},
								twitterValidation.message
							),
					],
					createElement( TestPostButton, {
						key: 'twitter-test',
						platform: 'twitter',
					} ),
				] ),
				createElement( 'div', { key: 'linkedin-group', style: { marginBottom: '20px' } }, [
					createElement(
						Button,
						{
							key: 'toggle-linkedin-keys',
							variant: 'secondary',
							onClick: () => setShowLinkedinKeys( ! showLinkedinKeys ),
							style: { marginBottom: '10px' },
						},
						showLinkedinKeys ? 'Hide LinkedIn API Keys' : 'Show LinkedIn API Keys'
					),

					showLinkedinKeys && [
						createElement( TextControl, {
							key: 'linkedin-client-id-input',
							label: 'LinkedIn Client ID',
							value: settings.linkedin_client_id || '',
							onChange: value =>
								setSettings( {
									...settings,
									linkedin_client_id: value,
								} ),
							help: __( 'Your LinkedIn Application Client ID' ),
						} ),
						createElement( TextControl, {
							key: 'linkedin-client-secret-input',
							label: 'LinkedIn Client Secret',
							value: settings.linkedin_client_secret || '',
							onChange: value =>
								setSettings( {
									...settings,
									linkedin_client_secret: value,
								} ),
							type: 'password',
							help: __( 'Your LinkedIn Application Client Secret' ),
						} ),
						createElement( LinkedInTokenStatus, {
							key: 'linkedin-token-status',
							tokenExpiresTimestamp: settings.linkedin_token_expires || null,
						} ),
						createElement(
							Button,
							{
								key: 'connect-linkedin-button',
								variant: 'primary',
								onClick: validateLinkedinCredentials,
								style: { marginTop: '10px' },
							},
							settings.linkedin_access_token ? __( 'Reconnect LinkedIn' ) : __( 'Connect LinkedIn' )
						),

						linkedinValidation &&
							createElement(
								Notice,
								{
									key: 'linkedin-validation-notice',
									status: linkedinValidation.success ? 'success' : 'error',
									isDismissible: true,
									onRemove: () => setLinkedinValidation( null ),
									style: { marginTop: '10px' },
								},
								linkedinValidation.message
							),

						settings.linkedin_access_token &&
							createElement(
								Notice,
								{
									key: 'linkedin-connected-notice',
									status: 'success',
									isDismissible: false,
									style: { marginTop: '10px' },
								},
								__( 'LinkedIn account connected successfully!' )
							),
					],

					createElement( TestPostButton, {
						key: 'linkedin-test',
						platform: 'linkedin',
						disabled: ! settings.linkedin_access_token,
					} ),
				] ),
				createElement( 'div', { key: 'instagram-group', style: { marginBottom: '20px' } }, [
					createElement(
						Button,
						{
							key: 'toggle-instagram-keys',
							variant: 'secondary',
							onClick: () => setShowInstagramKeys( ! showInstagramKeys ),
							style: { marginBottom: '10px' },
						},
						showInstagramKeys ? __( 'Hide Instagram API Keys' ) : __( 'Show Instagram API Keys' )
					),
					showInstagramKeys && [
						createElement( TextControl, {
							key: 'instagram-api-input',
							label: 'Instagram Page Access Token',
							value: settings.instagram_api_key,
							onChange: value =>
								setSettings( {
									...settings,
									instagram_api_key: value,
								} ),
						} ),
					],
					createElement( TestPostButton, {
						key: 'instagram-test',
						platform: 'instagram',
					} ),
				] ),
				createElement(
					Button,
					{
						key: 'save-button',
						isPrimary: true,
						onClick: handleSave,
						isBusy: isSaving,
					},
					__( 'Save Settings' )
				),
			] ),
		] ),
	] );
};

export default SettingsPage;
