import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { tap } from 'rxjs/operators';
import { Observable, BehaviorSubject } from 'rxjs';


export interface User {
    id: number;
    email: string;
    name?: string;
    role?: string;
}


@Injectable({
  providedIn: 'root'  
})
export class AuthService {
    private readonly STORAGE_KEY = 'currentUser';
    private currentUserSubject = new BehaviorSubject<User | null>(null);
    public currentUser$ = this.currentUserSubject.asObservable();

    constructor(private http: HttpClient) {
        const user = localStorage.getItem(this.STORAGE_KEY);
        if(user) {
            try {
                this.currentUserSubject.next(JSON.parse(user) as User);
            }   catch {
                localStorage.removeItem(this.STORAGE_KEY);
            }
        }
    }

    login(email: string, password: string): Observable<any> {
        return this.http.post<any>('/api/auth/login', { email, password })
        .pipe(tap(user => this.storeUserData(user)));
    }

    private storeUserData(user: any) {
        localStorage.setItem(this.STORAGE_KEY, JSON.stringify(user));
        this.currentUserSubject.next(user);
    }


    register(userData: any): Observable<any> {
        return this.http.post<any>('/api/auth/register', userData)
        .pipe(tap(user => this.storeUserData(user)));
    }

    logout() {
        localStorage.removeItem(this.STORAGE_KEY);
        this.currentUserSubject.next(null);
    }

    get isLoggedIn(): boolean {
        return !!this.currentUserSubject.value;
    }
}