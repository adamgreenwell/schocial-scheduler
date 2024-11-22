import { useState } from 'react';
import { __ } from '@wordpress/i18n';
import { Button, Notice } from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';

const PostNowButton = ( { platform, postId } ) => {
	const [ isPosting, setIsPosting ] = useState( false );
	const [ result, setResult ] = useState( null );

	const handlePostNow = async () => {
		if ( ! window.confirm( `Are you sure you want to post to ${ platform } now?` ) ) {
			// eslint-disable-line no-alert
			return;
		}

		setIsPosting( true );
		try {
			const response = await apiFetch( {
				path: `/schocial/v1/post-now/${ platform }/${ postId }`,
				method: 'POST',
			} );
			setResult( response );
		} catch ( error ) {
			setResult( {
				success: false,
				message: error.message || __( 'Failed to post to social media' ),
			} );
		}
		setIsPosting( false );
	};

	return (
		<div className="schocial-post-now">
			<Button
				variant="secondary"
				onClick={ handlePostNow }
				isBusy={ isPosting }
				disabled={ isPosting }
			>
				{ `Post to ${ platform } now` }
			</Button>

			{ result && (
				<Notice
					status={ result.success ? 'success' : 'error' }
					isDismissible={ true }
					onRemove={ () => setResult( null ) }
				>
					{ result.message }
				</Notice>
			) }
		</div>
	);
};

export default PostNowButton;
