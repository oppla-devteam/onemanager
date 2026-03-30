import { useState, useEffect } from 'react';

interface PushNotificationManagerProps {
  userId?: number;
}

export function usePushNotifications() {
  const [permission, setPermission] = useState<NotificationPermission>('default');
  const [subscription, setSubscription] = useState<PushSubscription | null>(null);
  const [isSupported, setIsSupported] = useState(false);

  useEffect(() => {
    // Check if notifications are supported
    if ('Notification' in window && 'serviceWorker' in navigator && 'PushManager' in window) {
      setIsSupported(true);
      setPermission(Notification.permission);
    }
  }, []);

  const requestPermission = async (): Promise<boolean> => {
    if (!isSupported) {
      console.warn('Push notifications are not supported in this browser');
      return false;
    }

    try {
      const result = await Notification.requestPermission();
      setPermission(result);
      
      if (result === 'granted') {
        await subscribeToPush();
        return true;
      }
      
      return false;
    } catch (error) {
      console.error('Error requesting notification permission:', error);
      return false;
    }
  };

  const subscribeToPush = async (): Promise<PushSubscription | null> => {
    try {
      const registration = await navigator.serviceWorker.ready;
      
      // Check for existing subscription
      let sub = await registration.pushManager.getSubscription();
      
      if (!sub) {
        // Create new subscription
        const vapidPublicKey = import.meta.env.VITE_VAPID_PUBLIC_KEY || '';
        
        sub = await registration.pushManager.subscribe({
          userVisibleOnly: true,
          applicationServerKey: urlBase64ToUint8Array(vapidPublicKey) as BufferSource,
        });
      }
      
      setSubscription(sub);
      
      // Send subscription to server
      await saveSubscriptionToServer(sub);
      
      return sub;
    } catch (error) {
      console.error('Error subscribing to push:', error);
      return null;
    }
  };

  const unsubscribeFromPush = async (): Promise<boolean> => {
    if (!subscription) return false;

    try {
      await subscription.unsubscribe();
      setSubscription(null);
      
      // Remove from server
      await removeSubscriptionFromServer(subscription);
      
      return true;
    } catch (error) {
      console.error('Error unsubscribing:', error);
      return false;
    }
  };

  const showNotification = async (title: string, options?: NotificationOptions) => {
    if (permission !== 'granted') {
      console.warn('Notification permission not granted');
      return;
    }

    try {
      const registration = await navigator.serviceWorker.ready;
      await registration.showNotification(title, {
        badge: '/icon-192.svg',
        icon: '/icon-192.svg',
        ...options,
      } as NotificationOptions);
    } catch (error) {
      console.error('Error showing notification:', error);
    }
  };

  return {
    permission,
    isSupported,
    subscription,
    requestPermission,
    subscribeToPush,
    unsubscribeFromPush,
    showNotification,
  };
}

// Helper functions
function urlBase64ToUint8Array(base64String: string): Uint8Array {
  const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
  const base64 = (base64String + padding)
    .replace(/-/g, '+')
    .replace(/_/g, '/');

  const rawData = window.atob(base64);
  const outputArray = new Uint8Array(rawData.length);

  for (let i = 0; i < rawData.length; ++i) {
    outputArray[i] = rawData.charCodeAt(i);
  }

  return outputArray;
}

async function saveSubscriptionToServer(subscription: PushSubscription): Promise<void> {
  const token = localStorage.getItem('token');
  
  await fetch('/api/push-subscriptions', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Authorization': `Bearer ${token}`,
    },
    body: JSON.stringify(subscription.toJSON()),
  });
}

async function removeSubscriptionFromServer(subscription: PushSubscription): Promise<void> {
  const token = localStorage.getItem('token');
  const endpoint = subscription.endpoint;
  
  await fetch('/api/push-subscriptions', {
    method: 'DELETE',
    headers: {
      'Content-Type': 'application/json',
      'Authorization': `Bearer ${token}`,
    },
    body: JSON.stringify({ endpoint }),
  });
}

// Notification types
export type NotificationType = 
  | 'invoice_created'
  | 'invoice_paid'
  | 'invoice_overdue'
  | 'contract_signed'
  | 'contract_expiring'
  | 'delivery_completed'
  | 'payment_received'
  | 'task_assigned'
  | 'task_completed'
  | 'sync_completed'
  | 'sync_failed';

export interface NotificationPayload {
  type: NotificationType;
  title: string;
  message: string;
  data?: Record<string, any>;
  url?: string;
}

// Notification manager component - Auto-enabled, no UI
export default function PushNotificationManager({ userId }: PushNotificationManagerProps) {
  const {
    permission,
    isSupported,
    requestPermission,
  } = usePushNotifications();

  // Auto-request permission on mount if not already granted or denied
  useEffect(() => {
    const autoEnableNotifications = async () => {
      if (isSupported && permission === 'default') {
        console.log('🔔 Auto-requesting push notification permission...');
        await requestPermission();
      }
    };

    autoEnableNotifications();
  }, [isSupported, permission, requestPermission]);

  // No UI - notifications are always active in background
  return null;
}
