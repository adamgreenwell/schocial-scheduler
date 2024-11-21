import { render, screen, fireEvent } from '@testing-library/react';
import Scheduler from '../../../src/components/Scheduler';

describe('Scheduler Component', () => {
    beforeEach(() => {
        // Mock WordPress data store
        wp.data.select.mockImplementation(() => ({
            getCurrentPostId: () => 1,
            getEditedPostAttribute: () => ({
                _schocial_schedule: {
                    facebook: '2024-01-01 12:00:00'
                }
            })
        }));
    });

    it('renders platform selection', () => {
        render(<Scheduler />);
        expect(screen.getByText('Facebook')).toBeInTheDocument();
        expect(screen.getByText('X (Twitter)')).toBeInTheDocument();
    });

    it('updates schedule when date is changed', () => {
        const mockEditPost = jest.fn();
        wp.data.dispatch.mockImplementation(() => ({
            editPost: mockEditPost
        }));

        render(<Scheduler />);
        const datePicker = screen.getByLabelText('Facebook Schedule');
        fireEvent.change(datePicker, { target: { value: '2024-02-01 12:00:00' } });

        expect(mockEditPost).toHaveBeenCalledWith({
            meta: {
                _schocial_schedule: expect.objectContaining({
                    facebook: '2024-02-01 12:00:00'
                })
            }
        });
    });
});