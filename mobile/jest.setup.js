// Runs before every test file.
//
// The first render of React Native host components lazily loads and
// evaluates a large module graph: ~650 ms on an idle machine, and several
// seconds when parallel Jest workers compete for the CPU. That cost used to be
// charged to whichever test happened to render first, which then hit Jest's
// 5 s per-test timeout only in loaded parallel runs (e.g. soil.submit's
// "a retry after a network failure..."). Pay it once per file here, in a hook
// with its own generous timeout, so tests only time their own behaviour.
import React from 'react';
import {
  ActivityIndicator, FlatList, Image, KeyboardAvoidingView, Modal, Pressable,
  ScrollView, Switch, Text, TextInput, TouchableOpacity, View,
} from 'react-native';
import { render } from '@testing-library/react-native';

beforeAll(() => {
  const tree = render(
    <KeyboardAvoidingView>
      <ScrollView>
        <View><Text>warm-up</Text></View>
        <TouchableOpacity><Text>a</Text></TouchableOpacity>
        <Pressable><Text>b</Text></Pressable>
        <TextInput value="" onChangeText={() => {}} />
        <Switch value={false} />
        <ActivityIndicator />
        <Image source={{ uri: 'file:///warm.png' }} />
        <FlatList data={[1]} renderItem={() => <Text>c</Text>} keyExtractor={(i) => String(i)} />
        <Modal visible={false}><Text>d</Text></Modal>
      </ScrollView>
    </KeyboardAvoidingView>
  );
  tree.unmount();
}, 60000);

// expo-clipboard (build 25): native module, faked for tests.
jest.mock('expo-clipboard', () => ({ setStringAsync: jest.fn(() => Promise.resolve(true)), getStringAsync: jest.fn(() => Promise.resolve('')) }));

// Offline scan queue (lib/scanQueue) and the map preview (build 27): native modules, faked for tests.
jest.mock('@react-native-async-storage/async-storage', () => require('@react-native-async-storage/async-storage/jest/async-storage-mock'));
jest.mock('expo-file-system/legacy', () => ({
  documentDirectory: 'file:///docs/',
  makeDirectoryAsync: jest.fn(() => Promise.resolve()),
  copyAsync: jest.fn(() => Promise.resolve()),
  deleteAsync: jest.fn(() => Promise.resolve()),
}));
jest.mock('react-native-webview', () => {
  const { View } = require('react-native');
  const WebView = (props) => require('react').createElement(View, { testID: 'webview', ...props });
  return { WebView, default: WebView };
});
