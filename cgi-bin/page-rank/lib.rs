/*
Implementation of Page Rank algorithm
paper "Google’s PageRank: The Math Behind the Search Engine"
*/

mod mat_mul;

use std::collections::HashMap;

pub struct PageRank {
    size: usize,
    hyperlink: Vec<Vec<f64>>,
}


impl PageRank {

    pub fn create_from_adjacency_matrix(web_graph: &Vec<Vec<u8>>) -> PageRank {
        /*
        create new page rank object from Adjacency matrix
        input reference to 2d vector Vec<Vec<u8>>
         */
        if web_graph.len() != web_graph[0].len() {
            return Self {
                size: 0,
                hyperlink: Vec::new(),
            };
        }

        let size: usize = web_graph.len();
        let mut hyperlink_matrix: Vec<Vec<f64>> = vec![vec![0.0; size]; size];

        for i in 0..size {
            let mut sum: i64 = 0;  // row sum
            for j in 0..size {
                sum += web_graph[i][j] as i64;
            }

            if sum > 0 {
                // has sub link
                for j in 0..size {
                    if web_graph[i][j] == 1 {
                        hyperlink_matrix[i][j] = 1.0 / (sum as f64);
                    }
                    else {
                        hyperlink_matrix[i][j] = 0.0;
                    }
                }
            }
            else {
                for j in 0..size {
                    hyperlink_matrix[i][j] = 1.0 / (size as f64);
                }
            }
        }
        return Self {
            size,
            hyperlink: hyperlink_matrix,
        };
    }

    pub fn page_rank(&self, alpha: f64, k: u64, threshold: f64) -> Option<HashMap<u8, f64>> {
        /*
        Calculate page rank of given hyperlink matrix
        Input
        alpha -- dampling factor (0, 1)
         */
        if alpha <= 0.0 || alpha > 1.0 {
            // if alpha not in (0, 1], quit and return None
            return None;
        }
        else if threshold >= 1.0 || threshold <= 0.0 {
            return None;
        }

        let mut rank_scores: HashMap<u8, f64> = HashMap::new();
        let ones: Vec<Vec<f64>> = vec![vec![1f64]; self.size];
        let v: Vec<Vec<f64>> = vec![vec![1f64 / (self.size as f64); self.size]; 1];
        let mut pi: Vec<Vec<f64>> = vec![vec![1f64 / (self.size as f64); self.size]; 1];

        // Google Matrix
        let google_matrix = mat_mul::matrix_addtion(
            &mat_mul::scaler_multiply(alpha, &self.hyperlink),
            &mat_mul::scaler_multiply(1.0 - alpha, &mat_mul::matrix_multiply(
                &ones,
                &v
            )));

        for _ in 0..=k{

            let pik =  mat_mul::matrix_multiply(&pi, &google_matrix);

            if mat_mul::length(&mat_mul::matrix_substract(&pik, &pi )[0] ) <= threshold  {
                // change pi to pik
                pi = pik;
                break;
            }
            else {
                // change pi to pik
                pi = pik;
            }

        }

        for i in 0..self.size{
            rank_scores.insert(i as u8, pi[0][i]);
        }

        return Some(rank_scores);
    }

    pub fn retrieve_hyperlink_matrix(&self) -> &Vec<Vec<f64>> {
        return &self.hyperlink;
    }

    pub fn dim(&self) -> usize {
        /*
        the dimension of page rank matrix
         */
        self.size
    }
}

#[cfg(test)]
mod tests {
    use std::collections::HashMap;

    #[test]
    fn it_works() {

        let adjacency_matrix: Vec<Vec<u8>> = vec![
            vec![0u8, 1u8, 0u8, 0u8],
            vec![0u8, 0u8, 1u8, 0u8],
            vec![1u8, 0u8, 0u8, 1u8],
            vec![0u8, 0u8, 0u8, 0u8]];


        let pr: super::PageRank = super::PageRank::create_from_adjacency_matrix(
            &adjacency_matrix
        );
        println!("{:?}", pr.retrieve_hyperlink_matrix());

        let rank_score = match pr.page_rank(0.85, 10, 0.01) {
            Some(rank_score) => rank_score,
            None => HashMap::new(),
        };
        print!("{:?}", rank_score);
    }
}
