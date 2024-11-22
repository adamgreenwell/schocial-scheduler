import { useState, useEffect } from 'react';

const { createElement, Fragment } = window.wp.element;
const apiFetch = window.wp.apiFetch;
const { __ } = window.wp.i18n;

const TestPostButton = ({ platform }) => {
	const [isLoading, setIsLoading] = useState(false);
	const [result, setResult] = useState(null);
	const Button = window.wp.components.Button;
	const Notice = window.wp.components.Notice;

	const handleTest = async () => {
		setIsLoading(true);
		try {
			const posts = await apiFetch({ path: '/wp/v2/posts?per_page=1' });
			if (!posts || posts.length === 0) {
				throw new Error('No posts found to test with');
			}

			const response = await apiFetch({
				path: `/schocial/v1/test-post/${platform}/${posts[0].id}`,
				method: 'POST',
			});
			setResult(response);
		} catch (error) {
			setResult({
				success: false,
				message: error.message,
			});
		}
		setIsLoading(false);
	};

	return createElement(
		'div',
		{
			key: `test-button-${platform}-container`,
			style: { marginTop: '10px' },
		},
		[
			createElement(
				Button,
				{
					key: `test-button-${platform}`,
					isPrimary: false,
					onClick: handleTest,
					isBusy: isLoading,
				},
				`${__('Test')} ${platform} ${__('Post')}`
			),
			result &&
				createElement(
					Notice,
					{
						key: `test-notice-${platform}`,
						status: result.success ? 'success' : 'error',
						isDismissible: true,
						onRemove: () => setResult(null),
						style: { marginTop: '10px' },
					},
					result.message
				),
		].filter(Boolean)
	); // Filter out null/undefined elements
};

const SettingsPage = () => {
	const [settings, setSettings] = useState({
		facebook_api_key: '',
		twitter_api_key: '',
		linkedin_api_key: '',
		instagram_api_key: '',
		enabled_platforms: {
			facebook: true,
			twitter: true,
			linkedin: true,
			instagram: true,
		},
	});
	const [isSaving, setIsSaving] = useState(false);

	// Get WordPress components
	const Card = window.wp.components.Card;
	const CardBody = window.wp.components.CardBody;
	const CardHeader = window.wp.components.CardHeader;
	const CheckboxControl = window.wp.components.CheckboxControl;
	const TextControl = window.wp.components.TextControl;
	const Button = window.wp.components.Button;

	const platforms = [
		{ id: 'facebook', label: 'Facebook' },
		{ id: 'twitter', label: 'X (Twitter)' },
		{ id: 'linkedin', label: 'LinkedIn' },
		{ id: 'instagram', label: 'Instagram' },
	];

	useEffect(() => {
		apiFetch({ path: '/schocial/v1/settings' }).then((response) => {
			setSettings(response);
		});
	}, []);

	const handleSave = async () => {
		setIsSaving(true);
		try {
			const response = await apiFetch({
				path: '/schocial/v1/settings',
				method: 'POST',
				data: settings,
			});
			setSettings(response);
		} catch (error) {
			console.error('Failed to save settings:', error); // eslint-disable-line no-console
		}
		setIsSaving(false);
	};

	return createElement(Fragment, null, [
		createElement(Card, { key: 'enabled-platforms-card' }, [
			createElement(
				CardHeader,
				{ key: 'enabled-platforms-header' },
				createElement(
					'h2',
					{ key: 'enabled-platforms-title' },
					__('Enabled Platforms')
				)
			),
			createElement(CardBody, { key: 'enabled-platforms-body' }, [
				...platforms.map((platform) =>
					createElement(CheckboxControl, {
						key: `${platform.id}-checkbox`,
						label: platform.label,
						checked: settings.enabled_platforms?.[platform.id],
						onChange: (value) =>
							setSettings({
								...settings,
								enabled_platforms: {
									...settings.enabled_platforms,
									[platform.id]: value,
								},
							}),
					})
				),
				createElement(
					Button,
					{
						key: 'save-button-1',
						isPrimary: true,
						onClick: handleSave,
						isBusy: isSaving,
					},
					__('Save Settings')
				),
			]),
		]),
		createElement(Card, { key: 'api-keys-card' }, [
			createElement(
				CardHeader,
				{ key: 'api-keys-header' },
				createElement(
					'h2',
					{ key: 'api-keys-title' },
					__('Social Media API Keys')
				)
			),
			createElement(CardBody, { key: 'api-keys-body' }, [
				...platforms.map((platform) =>
					createElement(
						'div',
						{
							key: `${platform.id}-group`,
							style: { marginBottom: '20px' },
						},
						[
							createElement(TextControl, {
								key: `${platform.id}-input`,
								label: `${platform.label} API Key`,
								value: settings[`${platform.id}_api_key`],
								onChange: (value) =>
									setSettings({
										...settings,
										[`${platform.id}_api_key`]: value,
									}),
							}),
							createElement(TestPostButton, {
								key: `${platform.id}-test`,
								platform: platform.id,
							}),
						]
					)
				),
				createElement(
					Button,
					{
						key: 'save-button-2',
						isPrimary: true,
						onClick: handleSave,
						isBusy: isSaving,
					},
					__('Save Settings')
				),
			]),
		]),
	]);
};

export default SettingsPage;
