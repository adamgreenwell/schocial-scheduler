import { registerPlugin } from '@wordpress/plugins';
import { PluginSidebar, PluginSidebarMoreMenuItem } from '@wordpress/editor';
import { useSelect, useDispatch } from '@wordpress/data';
import { DateTimePicker, PanelBody } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

const SchocialSchedulerSidebar = () => {
    const postId = useSelect(select => select('core/editor').getCurrentPostId());
    const scheduleData = useSelect(select =>
        select('core/editor').getEditedPostAttribute('meta')?._schocial_schedule
    );
    const { editPost } = useDispatch('core/editor');

    const updateSchedule = (platform, datetime) => {
        const newSchedule = {
            ...scheduleData,
            [platform]: datetime
        };

        editPost({
            meta: {
                _schocial_schedule: newSchedule
            }
        });
    };

    const platforms = useSelect(select => {
        const settings = select('core/editor').getEditorSettings().schocial_settings;
        return [
            { id: 'facebook', label: 'Facebook' },
            { id: 'twitter', label: 'X (Twitter)' },
            { id: 'linkedin', label: 'LinkedIn' },
            { id: 'instagram', label: 'Instagram' }
        ].filter(platform => settings?.enabled_platforms?.[platform.id]);
    });

    return (
        <>
            <PluginSidebar
                name="schocial-scheduler"
                title={__('Schocial Scheduler')}
            >
                {platforms.map(({ id, label }) => (
                    <PanelBody key={id} title={label} __nextHasNoMarginBottom={true}>
                        <DateTimePicker
                            currentDate={scheduleData?.[id]}
                            onChange={(date) => updateSchedule(id, date)}
                            is12Hour={true}
                        />
                    </PanelBody>
                ))}
            </PluginSidebar>
            <PluginSidebarMoreMenuItem
                target="schocial-scheduler"
            >
                {__('Schocial Scheduler')}
            </PluginSidebarMoreMenuItem>
        </>
    );
};

registerPlugin('schocial-scheduler', {
    render: SchocialSchedulerSidebar,
    icon: 'share'
});