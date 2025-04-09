import { Routes } from '@angular/router';

// Admin imports
import { AdminLoginComponent } from './components/admin-login/admin-login.component';
import { AdminDashboardComponent } from './components/adminDashboard/admin-dashboard/admin-dashboard.component';
import { AdminUsersComponent } from './components/adminDashboard/admin-users/admin-users.component';
import { AdminPropertiesComponent } from './components/adminDashboard/admin-properties/admin-properties.component';
import { AdminBookingsComponent } from './components/adminDashboard/admin-bookings/admin-bookings.component';
import { AdminActivitiesComponent } from './components/adminDashboard/admin-activities/admin-activities.component';
import { AdminSettingsComponent } from './components/adminDashboard/admin-settings/admin-settings.component';
import { AdminLayoutComponent } from './components/adminDashboard/admin-layout/admin-layout.component';
import { AdminStatisticsComponent } from './components/adminDashboard/admin-statistics/admin-statistics.component';

// User imports
import { LoginComponent } from './auth/login/login.component';
import { SignUpComponent } from './auth/sign-up/sign-up.component';
import { ForgotPasswordComponent } from './auth/forgot-password/forgot-password.component';
import { HomeComponent } from './components/home/home.component';

// Dashboard components
import { ProfileComponent } from './profile/profile.component';
import { MyPropertiesComponent } from './my-properties/my-properties.component';
import { FavoritesComponent } from './favorites/favorites.component';
import { AppointmentsComponent } from './appointments/appointments.component';
import { MessagesComponent } from './messages/messages.component';
import { StatisticsComponent } from './statistics/statistics.component';
import { SettingsComponent } from './settings/settings.component';
import { MainDashboardComponent } from './main-dashboard/main-dashboard.component';
import { UserDashboardComponent } from './user-dashboard/user-dashboard.component';

export const routes: Routes = [
  // Default route
  { path: '', redirectTo: 'home', pathMatch: 'full' },

  // Public routes
  { path: 'login', component: LoginComponent },
  { path: 'sign-up', component: SignUpComponent },
  { path: 'forgot-password', component: ForgotPasswordComponent },
  { path: 'home', component: HomeComponent },

  // User dashboard routes
  {
    path: '',
    component: MainDashboardComponent,
    children: [
      { path: 'dashboard', component: UserDashboardComponent },
      { path: 'profile', component: ProfileComponent },
      { path: 'properties', component: MyPropertiesComponent },
      { path: 'favorites', component: FavoritesComponent },
      { path: 'appointments', component: AppointmentsComponent },
      { path: 'messages', component: MessagesComponent },
      { path: 'statistics', component: StatisticsComponent },
      { path: 'settings', component: SettingsComponent },
      { path: '', redirectTo: 'dashboard', pathMatch: 'full' }
    ]
  },

  // Admin routes
  { path: 'admin/login', component: AdminLoginComponent },
  { 
    path: 'admin', 
    component: AdminDashboardComponent,
    // canActivate: [authGuard], // يمكنك تفعيله لاحقًا
    data: { roles: ['admin', 'super-admin'] }
  },
  {
    path: 'admin/layout',
    component: AdminLayoutComponent,
    // canActivate: [authGuard],
    data: { roles: ['admin', 'super-admin'] },
    children: [
      { path: '', redirectTo: 'users', pathMatch: 'full' },
      { path: 'users', component: AdminUsersComponent },
      { path: 'properties', component: AdminPropertiesComponent },
      { path: 'bookings', component: AdminBookingsComponent },
      { path: 'activities', component: AdminActivitiesComponent },
      { 
        path: 'settings', 
        component: AdminSettingsComponent,
        data: { roles: ['super-admin'] }
      },
      { 
        path: 'statistics', 
        component: AdminStatisticsComponent,
        data: { roles: ['super-admin'] }
      }
    ]
  },

  // Wildcard route
  { path: '**', redirectTo: 'home' }
];