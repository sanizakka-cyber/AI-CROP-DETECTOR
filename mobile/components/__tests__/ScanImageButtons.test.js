import React from 'react';
import { render, screen, fireEvent } from '@testing-library/react-native';
import ScanImageButtons from '../ScanImageButtons';

test('shows Take Picture, Select Picture and Browse Files, and no drag-and-drop', () => {
  const onPick = jest.fn();
  render(<ScanImageButtons onPick={onPick} />);
  fireEvent.press(screen.getByText('Take Picture'));
  fireEvent.press(screen.getByText('Select Picture'));
  fireEvent.press(screen.getByText('Browse Files'));
  expect(onPick.mock.calls.map(c => c[0])).toEqual(['camera', 'gallery', 'files']);
  expect(screen.queryByText(/drag/i)).toBeNull();
});

test('disabled while a scan is running', () => {
  const onPick = jest.fn();
  render(<ScanImageButtons onPick={onPick} disabled />);
  fireEvent.press(screen.getByText('Browse Files'));
  expect(onPick).not.toHaveBeenCalled();
});
