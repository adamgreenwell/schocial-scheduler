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

const InstagramPlatform = ( { enabled, schedule, onToggle, onScheduleChange, postId } ) => {
	return (
		<PanelBody title={ __( 'Instagram' ) } initialOpen={ false }>
			<PanelRow>
				<Flex direction="column" gap={ 4 }>
					<ToggleControl
						label={ __( 'Enable Instagram sharing' ) }
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
								<PostNowButton platform="instagram" postId={ postId } />
							</FlexBlock>
						</>
					) }
				</Flex>
			</PanelRow>
		</PanelBody>
	);
};

export default InstagramPlatform;
