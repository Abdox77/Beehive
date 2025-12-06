import { ComponentFixture, TestBed } from '@angular/core/testing';

import { Intervention } from './intervention';

describe('Intervention', () => {
  let component: Intervention;
  let fixture: ComponentFixture<Intervention>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [Intervention]
    })
    .compileComponents();

    fixture = TestBed.createComponent(Intervention);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
