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
                next: () => this.router.navigate(['/dashboard']),
                error: err => this.error = err.error?.message || 'Login failed'
            });
    }
}
