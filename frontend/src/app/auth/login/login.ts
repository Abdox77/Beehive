import { Component } from '@angular/core';
import { FormBuilder, FormGroup, ReactiveFormsModule, Validators } from '@angular/forms';
import { Router, RouterModule } from '@angular/router';
import { AuthService } from '../services/auth.service';
import { finalize } from 'rxjs';
import { CommonModule } from '@angular/common';


@Component({
  selector: 'app-login',
  standalone: true,
  imports: [
    CommonModule,
    ReactiveFormsModule,
    RouterModule
  ],
  templateUrl: './login.html',
  styleUrl: './login.css'
})
export class LoginComponent {
    loginForm: FormGroup;
    error : string | null = null;
    loading = false;

    constructor(
        private formBuilder: FormBuilder,
        private authService: AuthService,
        private router: Router
    ) {
        this.loginForm = this.formBuilder.group({
            email: ['', [Validators.required, Validators.email]],
            password: ['', Validators.required]
        });
    }

    onSubmit() {
        if (this.loginForm.invalid) return;

        this.loading = true;
        this.error = null;

        const { email, password } = this.loginForm.value;

        this.authService.login(email, password)
            .pipe(finalize(() => this.loading = false))
            .subscribe({
                next: res => this.collectAndStoreToken(res),
                error: err => {
                    if (err.status === 0) {
                        this.error = err.error?.message || 'Cannot connect to server. Is the backend running?';
                    } else {
                        this.error = err.error?.message || 'Login failed';
                    }
                }
            });
    }

    collectAndStoreToken(res: any) {
        const token = res?.token;
        if (token) {
            localStorage.setItem('token', res['token']);
            this.router.navigate(['/dashboard']);
        }
        else {
            this.error = 'No token in response';
        }
    }
}
