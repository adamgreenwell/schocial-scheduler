import '@testing-library/jest-dom';

// Mock WordPress dependencies
global.wp = {
    data: {
        select: jest.fn(),
        dispatch: jest.fn(),
        subscribe: jest.fn()
    },
    element: {
        createElement: jest.fn()
    },
    i18n: {
        __: (text) => text,
        sprintf: jest.fn()
    }
};