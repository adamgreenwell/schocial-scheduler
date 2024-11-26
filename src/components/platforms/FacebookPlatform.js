import {
	DateTimePicker,
	PanelBody,
	PanelRow,
	ToggleControl,
	Flex,
	FlexBlock,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import PostNowButton from '../PostNowButton';

const FacebookPlatform = ( { enabled, schedule, onToggle, onScheduleChange, postId } ) => {
	return (
		<PanelBody title={ __( 'Facebook' ) } initialOpen={ true }>
			<PanelRow>
				<Flex direction="column" gap={ 4 }>
					<ToggleControl
						label={ __( 'Enable Facebook sharing' ) }
						checked={ Boolean( enabled ) }
						onChange={ onToggle }
					/>
					{ enabled && (
						<>
							<FlexBlock>
								<DateTimePicker
									currentDate={ schedule ? new Date( schedule ) : null }
									onChange={ onScheduleChange }
									is12Hour={ true }
								/>
							</FlexBlock>
							<FlexBlock>
								<PostNowButton platform="facebook" postId={ postId } />
							</FlexBlock>
						</>
					) }
				</Flex>
			</PanelRow>
		</PanelBody>
	);
};

export default FacebookPlatform;
