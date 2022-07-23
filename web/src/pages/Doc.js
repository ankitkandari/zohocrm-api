import * as React from "react";
import Card from 'react-bootstrap/Card';
import { Link } from "react-router-dom";

const Doc = () => {
  return (
    <div className="row">
      <div className="col-sm-12 mt-5">
        <Card>
          <Card.Body>Find the API documentation here: <a href={"https://documenter.getpostman.com/view/8174648/UzXKWeHw"}>https://documenter.getpostman.com/view/8174648/UzXKWeHw</a></Card.Body>
        </Card>
      </div>
    </div>
  );
};

export default Doc;
