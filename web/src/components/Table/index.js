import Table from "react-bootstrap/Table";

function ZTable({ items }) {
  return (
    <Table   hover size="sm" responsive>
      <thead>
        <tr>
          <th>ID</th>
          <th>Email</th>
          <th>First Name</th>
          <th>Last Name</th>
          <th>City</th>
          <th>State</th>
          <th>Company</th>
        </tr>
      </thead>
      <tbody>
        {items &&
          items.map((item,indx) => {
            return (
              <tr key={indx}>
                <td>{item?.id}</td>
                <td>{item?.Email}</td>
                <td>{item?.First_Name}</td>
                <td>{item?.Last_Name}</td>
                <td>{item?.City}</td>
                <td>{item?.State}</td>
                <td>{item?.Company}</td>
              </tr>
            );
          })}
      </tbody>
    </Table>
  );
}

export default ZTable;
