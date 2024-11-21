import { schedulePosts } from '../../../src/utils/api';

describe('API Utils', () => {
    it('schedules posts successfully', async () => {
        global.fetch = jest.fn(() =>
            Promise.resolve({
                ok: true,
                json: () => Promise.resolve({ success: true })
            })
        );

        const result = await schedulePosts({
            facebook: '2024-01-01 12:00:00',
            twitter: '2024-01-01 13:00:00'
        });

        expect(result.success).toBe(true);
        expect(fetch).toHaveBeenCalledWith(
            expect.stringContaining('/wp-json/schocial/v1/schedule'),
            expect.objectContaining({
                method: 'POST'
            })
        );
    });
});