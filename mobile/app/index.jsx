import { Redirect } from 'expo-router';
import { useAuth } from '../context/AuthContext';
import AuthSplash from '../components/AuthSplash';

// App start: wait for the saved login to be checked, then go straight to
// Home, or to sign-in only when there is no valid login. It used to redirect
// to sign-in at once, so a signed-in farmer saw the sign-in form until the
// check finished.
export default function Index() {
  const { user, loading } = useAuth();
  if (loading) return <AuthSplash />;
  return <Redirect href={user ? '/(tabs)/home' : '/(auth)/login'} />;
}
