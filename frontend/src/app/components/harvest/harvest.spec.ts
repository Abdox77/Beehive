import { ComponentFixture, TestBed } from '@angular/core/testing';

import { Harvest } from './harvest';

describe('Harvest', () => {
  let component: Harvest;
  let fixture: ComponentFixture<Harvest>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [Harvest]
    })
    .compileComponents();

    fixture = TestBed.createComponent(Harvest);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
