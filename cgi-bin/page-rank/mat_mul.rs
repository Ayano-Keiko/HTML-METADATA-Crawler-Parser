/*
Matrix Operation tools
*/

pub fn matrix_multiply(a: &Vec<Vec<f64>>, b: &Vec<Vec<f64>>) -> Vec<Vec<f64>> {
    /*
    Matrix Multiplication
    [M, k] * [k, N] --> [M, N]
     */
    let size: (usize, usize, usize) = (a.len(), b[0].len(), a[0].len());

    let mut result: Vec<Vec<f64>> = vec![vec![0.0; size.1]; size.0];

    for i in 0..size.0 {
        for j in 0..size.1 {
            // row i in a dot product col j in b
            // result[i][j]; item i, j
            for k in 0..size.2 {
                result[i][j] += a[i][k] * b[k][j];
            }
        }
    }

    result
}

pub fn matrix_addtion(a: &Vec<Vec<f64>>, b: &Vec<Vec<f64>>) -> Vec<Vec<f64>> {
    /*
    element-wise matrix addition
     */

    let size: (usize, usize) = (a.len(), a[0].len());
    let mut result: Vec<Vec<f64>> = vec![vec![0.0; size.1]; size.0];

    for i in 0..size.0 {
        for j in 0..size.1 {
            result[i][j] = a[i][j] + b[i][j];
        }
    }

    result
}

pub fn scaler_multiply( scaler: f64, a: &Vec<Vec<f64>>) -> Vec<Vec<f64>> {
    /*
    Scaler Multiplication
    element-wise multiply
    scaler * [M, N]
     */
    let size: (usize, usize) = (a.len(), a[0].len());
    let mut result: Vec<Vec<f64>> = vec![vec![0.0; size.1]; size.0];

    for i in 0..size.0 {
        for j in 0..size.1 {
            result[i][j] = scaler * a[i][j];
        }
    }

    result
}

pub fn matrix_substract(a: &Vec<Vec<f64>>, b: &Vec<Vec<f64>>) -> Vec<Vec<f64>> {
    /*
    Matrix A - Matrix B
     */

    let size: (usize, usize) = (a.len(), a[0].len());
    let mut result: Vec<Vec<f64>> = vec![vec![0.0; size.1]; size.0];

    for i in 0..size.0 {
        for j in 0..size.1 {
            result[i][j] = a[i][j] - b[i][j];
        }
    }

    result
}

pub fn length(v: &Vec<f64>) -> f64 {
    /*
    get vector length
     */
    let mut len: f64 = 0.0;

    for item in v.iter() {
        len += item.powi(2);
    }

    return len.sqrt();
}