
import { AppRouter } from './routes';
import { ToastProvider } from '@/components/ui/Toast';

function App() {
  return (
    <ToastProvider>
      <AppRouter />
    </ToastProvider>
  );
}

export default App;
