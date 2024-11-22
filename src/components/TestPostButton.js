import { useState } from 'react';

const { Button, Notice } = window.wp.components;
const apiFetch = window.wp.apiFetch;

const TestPostButton = ({ postId, platform }) => {
	const [isLoading, setIsLoading] = useState(false);
	const [result, setResult] = useState(null);

	const handleTest = async () => {
		setIsLoading(true);
		try {
			const response = await apiFetch({
				path: `/schocial/v1/test-post/${platform}/${postId}`,
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

	return (
		<div>
			<Button isPrimary={false} onClick={handleTest} isBusy={isLoading}>
				Test {platform} Post
			</Button>

			{result && (
				<Notice
					status={result.success ? 'success' : 'error'}
					isDismissible={false}
				>
					{result.message}
				</Notice>
			)}
		</div>
	);
};

export default TestPostButton;
