export interface Appointment {
  id?: number;
  date: string;
  time: string;
  client: string;
  property_id: number;
  purpose: 'Property Viewing' | 'Contract Signing' | 'Site Tour' | 'Consultation';
  status: 'Scheduled' | 'Confirmed' | 'Pending' | 'Cancelled';
  user_id?: number;
  property_title?: string; // إذا كنت تحتاجه لعرض العنوان في الـ table
}
