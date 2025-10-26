import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ReactiveFormsModule, FormBuilder, FormGroup, Validators } from '@angular/forms';
import { Router, RouterModule } from '@angular/router';
import { AuthService } from '../services/auth.service';

@Component({
  selector: 'app-register',
  standalone: true,
  imports: [
    CommonModule,
    ReactiveFormsModule,
    RouterModule
  ],
  templateUrl: './register.html',
  styleUrl: './register.css'
})
export class RegisterComponent {
    registerForm: FormGroup;
    loading = false;
    error : null | string = null;

    constructor(
        private formBuilder: FormBuilder,
        private authService: AuthService,
        private router: Router
    ) {
        this.registerForm = this.formBuilder.group({
            fullName: ['', Validators.required],
            email: ['', [Validators.required, Validators.email]],
            password: ['', [Validators.required, Validators.minLength(8)]],
            confirmPassword: ['', Validators.required]
        }, {
            validators: this.passwordMatchValidator
        });
    }

    passwordMatchValidator(group: FormGroup) {
        const password = group.get('password')?.value;
        const confirmPassword = group.get('confirmPassword')?.value;

        return password === confirmPassword ? null : { passwordMismatch: true };
    } 

    onSubmit() {
        if (this.registerForm.invalid) return;

        this.loading = true;
        this.error = null;

        const { fullName, email, password } = this.registerForm.value;

        this.authService.register({ fullName, email, password })
            .subscribe({
                next: () => {
                    this.router.navigate(['/auth/login']);
                },
                error: err => {
                    this.error = err.error?.message || 'Registration failed. Please try again.';
                    this.loading = false;
                }
            });
    }
}
