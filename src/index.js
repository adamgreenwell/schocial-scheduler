import { registerPlugin } from '@wordpress/plugins';
import { PluginSidebar, PluginSidebarMoreMenuItem } from '@wordpress/editor';
import { useSelect, useDispatch } from '@wordpress/data';
import { Notice } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import FacebookPlatform from './components/platforms/FacebookPlatform';
import TwitterPlatform from './components/platforms/TwitterPlatform';
import LinkedInPlatform from './components/platforms/LinkedInPlatform';
import InstagramPlatform from './components/platforms/InstagramPlatform';

const SchocialSchedulerSidebar = () => {
	const scheduleData = useSelect( select => {
		if ( ! select( 'core/editor' ) ) {
			console.log( 'core/editor store not found' );
			return {
				platforms: {},
				schedule: {},
			};
		}

		const meta = select( 'core/editor' ).getEditedPostAttribute( 'meta' ) || {};

		return {
			platforms: meta._schocial_platforms || {
				facebook: false,
				twitter: false,
				linkedin: false,
				instagram: false,
			},
			schedule: meta._schocial_schedule || {
				facebook: null,
				twitter: null,
				linkedin: null,
				instagram: null,
			},
		};
	}, [] );

	const { getCurrentPostId } = useSelect(
		select => ( {
			getCurrentPostId: () => select( 'core/editor' ).getCurrentPostId(),
		} ),
		[]
	);

	const { editPost } = useDispatch( 'core/editor' );

	const updateSchedule = ( platform, datetime ) => {
		const newSchedule = {
			...scheduleData.schedule,
			[ platform ]: datetime,
		};

		editPost( {
			meta: {
				...scheduleData,
				_schocial_schedule: newSchedule,
			},
		} );
	};

	const togglePlatform = ( platform, enabled ) => {
		const newPlatforms = {
			...scheduleData.platforms,
			[ platform ]: enabled,
		};

		const newSchedule = {
			...scheduleData.schedule,
			[ platform ]: enabled ? scheduleData.schedule[ platform ] : null,
		};

		editPost( {
			meta: {
				_schocial_platforms: newPlatforms,
				_schocial_schedule: newSchedule,
			},
		} );
	};

	const postId = getCurrentPostId();

	return (
		<>
			<PluginSidebar
				name="schocial-scheduler"
				title={ __( 'Schocial Scheduler' ) }
				icon="calendar-alt"
			>
				<Notice status="info" isDismissible={ false }>
					{ __( 'Configure your social media schedule' ) }
				</Notice>

				<FacebookPlatform
					enabled={ scheduleData.platforms.facebook }
					schedule={ scheduleData.schedule.facebook }
					onToggle={ enabled => togglePlatform( 'facebook', enabled ) }
					onScheduleChange={ date => updateSchedule( 'facebook', date ) }
					postId={ postId }
				/>

				<TwitterPlatform
					enabled={ scheduleData.platforms.twitter }
					schedule={ scheduleData.schedule.twitter }
					onToggle={ enabled => togglePlatform( 'twitter', enabled ) }
					onScheduleChange={ date => updateSchedule( 'twitter', date ) }
					postId={ postId }
				/>

				<LinkedInPlatform
					enabled={ scheduleData.platforms.linkedin }
					schedule={ scheduleData.schedule.linkedin }
					onToggle={ enabled => togglePlatform( 'linkedin', enabled ) }
					onScheduleChange={ date => updateSchedule( 'linkedin', date ) }
					postId={ postId }
				/>

				<InstagramPlatform
					enabled={ scheduleData.platforms.instagram }
					schedule={ scheduleData.schedule.instagram }
					onToggle={ enabled => togglePlatform( 'instagram', enabled ) }
					onScheduleChange={ date => updateSchedule( 'instagram', date ) }
					postId={ postId }
				/>
			</PluginSidebar>

			<PluginSidebarMoreMenuItem target="schocial-scheduler">
				{ __( 'Schocial Scheduler' ) }
			</PluginSidebarMoreMenuItem>
		</>
	);
};

export default SchocialSchedulerSidebar;

// Make sure the plugin is registered after DOM is ready
window.addEventListener( 'DOMContentLoaded', () => {
	registerPlugin( 'schocial-scheduler', {
		render: SchocialSchedulerSidebar,
		icon: 'calendar-alt',
	} );
} );
