import { Component, AfterViewInit } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Router } from '@angular/router';

@Component({
  selector: 'app-sign-up',
  standalone: true,
  imports: [FormsModule],
  templateUrl: './sign-up.component.html',
  styleUrls: ['./sign-up.component.css']
})
export class SignUpComponent implements AfterViewInit {
  first_name: string = '';
  last_name: string = '';
  email: string = '';
  phone_number: string = '';
  country: string = '';
  password: string = '';
  confirmPassword: string = '';
  terms_and_conditions: boolean = false;

  constructor(private http: HttpClient, private router: Router) {}

  onSubmit() {
    let isValid = true;

    // وظيفة للتحقق من صحة الإدخالات
    const validateInput = (input: HTMLInputElement | HTMLSelectElement, message: string, pattern?: RegExp) => {
      if (
        input.value.trim() === '' ||
        (pattern && !pattern.test(input.value)) ||
        (input.id === 'password' && input.value.length < 8) ||
        (input.id === 'first_name' && input.value.length < 3) ||
        (input.id === 'last_name' && input.value.length < 3)
      ) {
        input.classList.add('error');
        (input as HTMLInputElement).placeholder = message;
        isValid = false;
      } else {
        input.classList.remove('error');
      }
    };

    const first_nameInput = document.getElementById('first_name') as HTMLInputElement;
    const last_nameInput = document.getElementById('last_name') as HTMLInputElement;
    const emailInput = document.getElementById('email') as HTMLInputElement;
    const phone_numberInput = document.getElementById('phone_number') as HTMLInputElement;
    const countryInput = document.getElementById('country') as HTMLSelectElement;
    const passwordInput = document.getElementById('password') as HTMLInputElement;
    const confirmPasswordInput = document.getElementById('confirmPassword') as HTMLInputElement;
    const termsInput = document.getElementById('terms_and_conditions') as HTMLInputElement;

    // التحقق من البيانات
    validateInput(first_nameInput, 'First name must be at least 3 characters');
    validateInput(last_nameInput, 'Last name must be at least 3 characters');
    validateInput(emailInput, 'Please enter a valid email', /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/);
    validateInput(countryInput, 'Please select a country');
    validateInput(passwordInput, 'Password must be at least 8 characters');

    // التحقق من تطابق كلمة المرور
    if (passwordInput.value !== confirmPasswordInput.value) {
      confirmPasswordInput.classList.add('error');
      confirmPasswordInput.placeholder = 'Passwords must match';
      isValid = false;
    }

    // التحقق من الموافقة على الشروط
    if (!termsInput.checked) {
      termsInput.classList.add('error');
      isValid = false;
    }

    if (isValid) {
      const userData = {
        first_name: this.first_name,
        last_name: this.last_name,
        email: this.email,
        phone_number: this.phone_number || null,
        country: this.country || null,
        password: this.password,
        password_confirmation: this.confirmPassword,
        terms_and_conditions: this.terms_and_conditions ? 1 : 0,
      };

      const headers = new HttpHeaders({
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      });

      this.http.post('http://localhost:8000/api/v1/register', userData, { headers }).subscribe({
        next: () => {
          alert('Registration successful!');
          this.router.navigate(['/login']);
          this.resetForm();
        },
        error: (error) => {
          alert('Registration failed: ' + JSON.stringify(error.error));
        },
      });
    }
  }

  resetForm() {
    this.first_name = '';
    this.last_name = '';
    this.email = '';
    this.phone_number = '';
    this.country = '';
    this.password = '';
    this.confirmPassword = '';
    this.terms_and_conditions = false;
  }

  signInWithGoogle() {
    window.location.href = 'http://localhost:8000/api/v1/social/auth/google';
  }

  ngAfterViewInit() {
    document.querySelectorAll('input').forEach(input => {
      input.addEventListener('focus', () => {
        input.classList.remove('error');
      });
    });
  }
}
