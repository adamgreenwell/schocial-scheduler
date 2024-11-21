import { useState, useEffect } from 'react';
import apiFetch from '@wordpress/api-fetch';
import { Button, TextControl, Card, CardBody, CardHeader, CheckboxControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

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
            instagram: true
        }
    });
    const [isSaving, setIsSaving] = useState(false);
    const [lastSaved, setLastSaved] = useState(null);

    useEffect(() => {
        apiFetch({ path: '/schocial/v1/settings' }).then(response => {
            setSettings(response);
        });
    }, []);

    const handleSave = async () => {
        setIsSaving(true);
        try {
            const response = await apiFetch({
                path: '/schocial/v1/settings',
                method: 'POST',
                data: settings
            });
            setSettings(response);
        } catch (error) {
            console.error('Failed to save settings:', error);
        }
        setIsSaving(false);
    };

    return (
        <>
            <Card>
                <CardHeader>
                    <h2>{__('Enabled Platforms')}</h2>
                </CardHeader>
                <CardBody>
                    <CheckboxControl
                        label={__('Facebook')}
                        checked={settings.enabled_platforms?.facebook}
                        onChange={value => setSettings({
                            ...settings,
                            enabled_platforms: {
                                ...settings.enabled_platforms,
                                facebook: value
                            }
                        })}
                    />
                    <CheckboxControl
                        label={__('X (Twitter)')}
                        checked={settings.enabled_platforms?.twitter}
                        onChange={value => setSettings({
                            ...settings,
                            enabled_platforms: {
                                ...settings.enabled_platforms,
                                twitter: value
                            }
                        })}
                    />
                    <CheckboxControl
                        label={__('LinkedIn')}
                        checked={settings.enabled_platforms?.linkedin}
                        onChange={value => setSettings({
                            ...settings,
                            enabled_platforms: {
                                ...settings.enabled_platforms,
                                linkedin: value
                            }
                        })}
                    />
                    <CheckboxControl
                        label={__('Instagram')}
                        checked={settings.enabled_platforms?.instagram}
                        onChange={value => setSettings({
                            ...settings,
                            enabled_platforms: {
                                ...settings.enabled_platforms,
                                instagram: value
                            }
                        })}
                    />
                    <Button
                        isPrimary
                        onClick={handleSave}
                        isBusy={isSaving}
                    >
                        {__('Save Settings')}
                    </Button>
                </CardBody>
            </Card>
            <Card>
                <CardHeader>
                    <h2>{__('Social Media API Keys')}</h2>
                </CardHeader>
                <CardBody>
                    <TextControl
                        label={__('Facebook API Key')}
                        value={settings.facebook_api_key}
                        onChange={value => setSettings({...settings, facebook_api_key: value})}
                    />
                    <TextControl
                        label={__('Twitter API Key')}
                        value={settings.twitter_api_key}
                        onChange={value => setSettings({...settings, twitter_api_key: value})}
                    />
                    <TextControl
                        label={__('LinkedIn API Key')}
                        value={settings.linkedin_api_key}
                        onChange={value => setSettings({...settings, linkedin_api_key: value})}
                    />
                    <TextControl
                        label={__('Instagram API Key')}
                        value={settings.instagram_api_key}
                        onChange={value => setSettings({...settings, instagram_api_key: value})}
                    />
                    <Button
                        isPrimary
                        onClick={handleSave}
                        isBusy={isSaving}
                    >
                        {__('Save Settings')}
                    </Button>
                </CardBody>
            </Card>
        </>
    );
};

export default SettingsPage;