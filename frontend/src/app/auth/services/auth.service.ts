import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { tap } from 'rxjs/operators';
import { Observable, BehaviorSubject } from 'rxjs';

export interface User {
    id: number;
    email: string;
    name?: string;
}

const BACKEND_URL = 'http://localhost:8000';

@Injectable({
  providedIn: 'root'
})
export class AuthService {
    private readonly STORAGE_KEY = 'currentUser';
    private currentUserSubject = new BehaviorSubject<User | null>(null);
    public currentUser$ = this.currentUserSubject.asObservable();

    constructor(private http: HttpClient) {
        const token = localStorage.getItem('token');
        const user = localStorage.getItem(this.STORAGE_KEY);
        if (token && user) {
            try {
                this.currentUserSubject.next(JSON.parse(user) as User);
            }
            catch {
                localStorage.removeItem(this.STORAGE_KEY);
                localStorage.removeItem('token');
            }
        }
    }

    /**
     *
     * @param email
     * @param password
     * @returns Observable<User>
     */
    login(email: string, password: string): Observable<User> {
        const body =  {
          email: email,
          password: password
        }
        return this.http.post<User>(`${BACKEND_URL}/api/auth/login`, body)
        .pipe(tap(user => this.storeUserData(user)));
    }

    logout() {
        localStorage.removeItem(this.STORAGE_KEY);
        this.currentUserSubject.next(null);
    }

    /**
     *
     * @param userData
     * @returns
     */
    register(userData: any): Observable<any> {
        return this.http.post<any>(`${BACKEND_URL}/api/auth/register`, userData).
        pipe(tap(user => this.storeUserData(user)));
    }


    private storeUserData(user: any) {
        localStorage.setItem(this.STORAGE_KEY, JSON.stringify(user));
        this.currentUserSubject.next(user);
    }

    get isLoggerdIn() : boolean {
        return !!this.currentUserSubject.value;
    }
}
