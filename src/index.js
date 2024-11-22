import { registerPlugin } from '@wordpress/plugins';
import { PluginSidebar, PluginSidebarMoreMenuItem } from '@wordpress/editor';
import { useSelect, useDispatch } from '@wordpress/data';
import {
	DateTimePicker,
	PanelBody,
	PanelRow,
	Notice,
	ToggleControl,
	Flex,
	FlexBlock,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import PostNowButton from './components/PostNowButton';

const SchocialSchedulerSidebar = () => {
	const scheduleData = useSelect( select => {
		if ( ! select( 'core/editor' ) ) {
			console.log( 'core/editor store not found' ); // eslint-disable-line no-console
			return {
				platforms: {},
				schedule: {},
			};
		}

		const meta = select( 'core/editor' ).getEditedPostAttribute( 'meta' ) || {};

		// Ensure we have objects even if meta is undefined
		const platforms = meta._schocial_platforms || {
			facebook: false,
			twitter: false,
			linkedin: false,
			instagram: false,
		};

		const schedule = meta._schocial_schedule || {
			facebook: null,
			twitter: null,
			linkedin: null,
			instagram: null,
		};

		return { platforms, schedule };
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

		// If disabling, set schedule to null for this platform
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

	const platforms = [
		{ id: 'facebook', label: 'Facebook' },
		{ id: 'twitter', label: 'X (Twitter)' },
		{ id: 'linkedin', label: 'LinkedIn' },
		{ id: 'instagram', label: 'Instagram' },
	];

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

				{ platforms.map( ( { id, label } ) => (
					<PanelBody key={ id } title={ label } initialOpen={ id === 'facebook' }>
						<PanelRow>
							<Flex direction="column" gap={ 4 }>
								<ToggleControl
									label={ `Enable ${ label } sharing` }
									checked={ Boolean( scheduleData.platforms[ id ] ) }
									onChange={ enabled => togglePlatform( id, enabled ) }
								/>
								{ scheduleData.platforms[ id ] && (
									<>
										<FlexBlock>
											<DateTimePicker
												currentDate={
													scheduleData.schedule[ id ]
														? new Date( scheduleData.schedule[ id ] )
														: null
												}
												onChange={ date => updateSchedule( id, date ) }
												is12Hour={ true }
											/>
										</FlexBlock>
										<FlexBlock>
											<PostNowButton platform={ id } postId={ getCurrentPostId() } />
										</FlexBlock>
									</>
								) }
							</Flex>
						</PanelRow>
					</PanelBody>
				) ) }
			</PluginSidebar>
			<PluginSidebarMoreMenuItem target="schocial-scheduler">
				{ __( 'Schocial Scheduler' ) }
			</PluginSidebarMoreMenuItem>
		</>
	);
};

// Make sure the plugin is registered after DOM is ready
window.addEventListener( 'DOMContentLoaded', () => {
	registerPlugin( 'schocial-scheduler', {
		render: SchocialSchedulerSidebar,
		icon: 'calendar-alt',
	} );
} );
