import { Inter } from 'next/font/google';
import './globals.css';
import { AuthProvider } from './context/AuthContext';

const inter = Inter({ subsets: ['latin'] });

export const metadata = {
  title: 'MSAS Livestock & Agro Services | Smart Agriculture Platform',
  description:
    'MSAS Livestock & Agro Services is a smart digital agricultural platform supporting livestock farmers, poultry owners, and agribusiness operators in Nigeria with AI-powered diagnostics, expert consultations, and marketplace access.',
  keywords:
    'livestock management, agriculture Nigeria, poultry farming, veterinary consultation, Katsina, MSAS',
  openGraph: {
    title: 'MSAS Livestock & Agro Services',
    description: 'Smart Agriculture | Healthy Livestock | Better Future',
    type: 'website',
  },
};

export default function RootLayout({ children }) {
  return (
    <html lang="en" suppressHydrationWarning>
      <body className={inter.className}>
        <AuthProvider>{children}</AuthProvider>
      </body>
    </html>
  );
}
